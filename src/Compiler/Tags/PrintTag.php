<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Tags;

use Minty\Compiler\Compiler;
use Minty\Compiler\Nodes\FunctionNode;
use Minty\Compiler\Nodes\TagNode;
use Minty\Compiler\Parser;
use Minty\Compiler\Stream;
use Minty\Compiler\Tag;
use Minty\Compiler\Token;

class PrintTag extends Tag
{

    public function getTag()
    {
        return 'print';
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $expression = $parser->parseExpression($stream);
        if ($stream->current()->test(Token::PUNCTUATION, ':')) {
            $node = new TagNode(
                $parser->getEnvironment()->getTag('set')
            );
            $node->addChild($parser->parseExpression($stream), 'value_0');
            $node->addChild($expression, 'expression_0');
            $node->addData('variables', 1);
        } else {
            $node = new TagNode($this);
            $node->addChild($expression, 'expression');
        }

        return $node;
    }

    public function compile(Compiler $compiler, TagNode $node)
    {
        $expression = $node->getChild('expression');

        if (!$node->getData('is_safe')) {
            $arguments = array($expression);

            if ($node->hasChild('filter_for')) {
                $arguments[] = $node->getChild('filter_for');
            }

            $function = new FunctionNode('filter', $arguments);
            $function->addChild($expression);
            $expression = $function;
        }
        $compiler
            ->indented('echo ')
            ->compileNode($expression)
            ->add(';');
    }
}
