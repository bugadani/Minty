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
use Minty\Compiler\Nodes\TempVariableNode;
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
        if ($node->hasChild('else')) {
            //extract data to be traversed
            $compiler
                ->indented('$temp = ')
                ->compileNode($node->getChild('source'))
                ->add(';');

            //generate check and code for the 'else' branch
            $compiler
                ->indented('if(empty($temp)) {')
                ->indent()
                ->compileNode($node->getChild('else'))
                ->outdent()
                ->indented('} else {')
                ->indent();

            $source = new TempVariableNode('temp');
        } else {
            $source = $node->getChild('source');
        }

        $compiler
            ->indented('foreach(')
            ->compileNode($source)
            ->add(' as ');

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
            $arguments = [];
            for ($i = 0; $i < $variables; ++$i) {
                $arguments[] = $node->getChild('loop_variable_' . $i);
            }
            $compiler
                ->add('$loopVariable) {')
                ->indent()
                ->indented('list')
                ->compileArgumentList($arguments)
                ->add(' = $loopVariable;');
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
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $node = new TagNode($this);

        $loopVar = $this->parseVariableNode($stream);
        if ($stream->nextTokenIf(Token::PUNCTUATION, [':', '=>'])) {
            $node->addChild($loopVar, 'loop_key');
            $loopVar = $this->parseVariableNode($stream);
        }
        $node->addChild($loopVar, 'loop_variable_0');

        $i = 1;
        while ($stream->nextTokenIf(Token::PUNCTUATION, ',')) {
            $node->addChild(
                $this->parseVariableNode($stream),
                'loop_variable_' . $i++
            );
        }
        $node->addData('variables', $i);

        $stream->expect(Token::OPERATOR, 'in');

        $node->addChild($parser->parseExpression($stream), 'source');
        $node->addChild($parser->parseBlock($stream, ['else', 'endfor']), 'loop_body');

        if ($stream->next()->test(Token::TAG_END, 'else')) {
            $node->addChild($parser->parseBlock($stream, 'endfor'), 'else');
            $stream->expect(Token::TAG_END);
        }

        return $node;
    }

    /**
     * @param Stream $stream
     *
     * @return VariableNode
     */
    private function parseVariableNode(Stream $stream)
    {
        return new VariableNode(
            $stream
                ->expect(Token::VARIABLE)
                ->getValue()
        );
    }
}
