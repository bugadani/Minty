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

class UnsetTag extends Tag
{

    public function getTag()
    {
        return 'unset';
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $node = new TagNode($this);

        do {
            $node->addChild($parser->parseExpression($stream));
        } while ($stream->current()->test(Token::PUNCTUATION, ','));

        return $node;
    }

    public function compile(Compiler $compiler, TagNode $node)
    {
        foreach ($node->getChildren() as $child) {
            $compiler
                ->indented('unset(')
                ->compileNode($child)
                ->add(');');
        }
    }
}
