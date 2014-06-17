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
        if ($node->getData('save_temp_var')) {
            $compiler
                ->indented('if (isset($temp))')
                ->add(' {')
                ->indent();

            if ($node->getData('create_stack')) {
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
        if ($node->getData('save_temp_var')) {
            $compiler->indented('if(isset($stack)) {');
            $compiler->indent();
            $compiler->indented('$temp = array_pop($stack);');
            if ($node->getData('create_stack')) {
                $compiler->indented('unset($stack);');
            }
            $compiler->outdent();
            $compiler->indented('}');
        }
    }

    public function parse(Parser $parser, Stream $stream)
    {
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
        $node->addChild($parser->parseBlock($stream, array('else', 'endfor')), 'loop_body');

        if ($stream->current()->test(Token::TAG, 'else')) {
            $node->addChild($parser->parseBlock($stream, 'endfor'), 'else');
        }

        return $node;
    }
}
