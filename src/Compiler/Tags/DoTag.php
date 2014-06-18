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

class DoTag extends Tag
{

    public function getTag()
    {
        return 'do';
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $node = new TagNode($this);
        $node->addChild(
            $parser->parseExpression($stream),
            'expression'
        );

        return $node;
    }

    public function compile(Compiler $compiler, TagNode $node)
    {
        $compiler
            ->indented('')
            ->compileNode($node->getChild('expression'))
            ->add(';');
    }
}
