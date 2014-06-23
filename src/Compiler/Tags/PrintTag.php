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
        $compiler
            ->indented('echo ')
            ->compileNode($node->getChild('expression'))
            ->add(';');
    }
}
