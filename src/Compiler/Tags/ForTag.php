<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Tags;

use Minty\Compiler\Compiler;
use Minty\Compiler\Nodes\TagNode;
use Minty\Compiler\Nodes\VariableNode;
use Minty\Compiler\Parser;
use Minty\Compiler\Stream;
use Minty\Compiler\Tag;
use Minty\Compiler\Token;

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

        $variables = $node->getData('variables');
        if ($variables === 1) {
            $compiler
                ->compileNode($node->getChild('loop_variable_0'))
                ->add(') {')
                ->indent();
        } else {
            $compiler
                ->add('$loopVariable) {')
                ->indent()
                ->indented('list(');

            for ($i = 0; $i < $variables; ++$i) {
                if ($i > 0) {
                    $compiler->add(', ');
                }
                $compiler->compileNode($node->getChild('loop_variable_' . $i));
            }

            $compiler->add(') = $loopVariable;');
        }
        $compiler
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

        $i       = 0;
        $loopVar = $stream->expect(Token::VARIABLE)->getValue();
        if ($stream->nextTokenIf(Token::PUNCTUATION, array(':', '=>'))) {
            $node->addChild(new VariableNode($loopVar), 'loop_key');
            $loopVar = $stream->expect(Token::VARIABLE)->getValue();
        }
        $node->addChild(new VariableNode($loopVar), 'loop_variable_' . $i++);

        while ($stream->next()->test(Token::PUNCTUATION, ',')) {
            $loopVar = $stream->expect(Token::VARIABLE)->getValue();
            $node->addChild(new VariableNode($loopVar), 'loop_variable_' . $i++);
        }
        $node->addData('variables', $i);

        $stream->expectCurrent(Token::OPERATOR, 'in');

        $node->addChild($parser->parseExpression($stream), 'source');
        $node->addChild($parser->parseBlock($stream, array('else', 'endfor')), 'loop_body');

        if ($stream->next()->test(Token::TAG_END, 'else')) {
            $node->addChild($parser->parseBlock($stream, 'endfor'), 'else');
            $stream->expect(Token::TAG_END);
        }

        return $node;
    }
}
