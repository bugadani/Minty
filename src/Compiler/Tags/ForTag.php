<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Tags;

use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\Nodes\TagNode;
use Modules\Templating\Compiler\Nodes\VariableNode;
use Modules\Templating\Compiler\Parser;
use Modules\Templating\Compiler\Stream;
use Modules\Templating\Compiler\Tag;
use Modules\Templating\Compiler\Token;

class ForTag extends Tag
{

    public function hasEndingTag()
    {
        return true;
    }

    public function getTag()
    {
        return 'for';
    }

    public function compile(Compiler $compiler, TagNode $node)
    {
        $data = $node->getData();
        if ($data['save_temp_var']) {
            $compiler
                ->indented('if (isset($temp))')
                ->add(' {')
                ->indent();

            if ($data['create_stack']) {
                $compiler->indented('$stack = array();');
            }

            $compiler
                ->indented('$stack[] = $temp;')
                ->outdent()
                ->indented('}');
        }

        if ($node->hasChild('else')) {
            $compiler
                ->indented('$temp = ')
                ->compileNode($node->getChild('source'))
                ->add(';')
                ->indented('if(empty($temp))')
                ->add(' {')
                ->indent()
                ->compileNode($node->getChild('else'))
                ->outdent()
                ->indented('}')
                ->add(' else')
                ->add(' {')
                ->indent()
                ->indented('foreach($temp');
        } else {
            $compiler
                ->indented('foreach(')
                ->compileNode($node->getChild('source'));
        }

        $compiler->add(' as ');

        if ($node->hasChild('loop_key')) {
            $compiler
                ->compileNode($node->getChild('loop_key'))
                ->add(' => ');
        }

        $compiler
            ->compileNode($node->getChild('loop_variable'))
            ->add(') {')
            ->indent()
            ->compileNode($node->getChild('loop_body'))
            ->outdent()
            ->indented('}');

        if ($node->hasChild('else')) {
            //bracket opened after if-empty check
            $compiler
                ->outdent()
                ->indented('}');
        }
        if ($data['save_temp_var']) {
            $compiler->indented('$temp = array_pop($stack);');
            if ($data['create_stack']) {
                $compiler->indented('unset($stack);');
            }
        }
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $elseTest = function (Token $token) {
            if ($token->test(Token::EXPRESSION_START, 'else')) {
                return true;
            }

            return $token->test(Token::TAG, 'endfor');
        };
        $endTest  = function (Token $token) {
            return $token->test(Token::TAG, 'endfor');
        };

        $node = new TagNode($this, array(
            'save_temp_var' => true,
            'create_stack'  => true
        ));

        $loopVar = $stream->expect(Token::VARIABLE)->getValue();
        if ($stream->nextTokenIf(Token::PUNCTUATION, ':')) {
            $node->addChild(new VariableNode($loopVar), 'loop_key');
            $loopVar = $stream->expect(Token::VARIABLE)->getValue();
        }
        $stream->expect(Token::OPERATOR, 'in');

        $node->addChild($parser->parseExpression($stream), 'source');
        $node->addChild(new VariableNode($loopVar), 'loop_variable');
        $node->addChild($parser->parse($stream, $elseTest), 'loop_body');

        if ($stream->current()->test(Token::EXPRESSION_START, 'else')) {
            $stream->expect(Token::EXPRESSION_END);
            $node->addChild($parser->parse($stream, $endTest), 'else');
        }

        return $node;
    }
}
