<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Tags;

use Minty\Compiler\Nodes\ExpressionNode;
use Minty\Compiler\Nodes\RootNode;
use Minty\Compiler\Parser;
use Minty\Compiler\Stream;
use Minty\Compiler\Tag;
use Minty\Compiler\Token;

class DoTag extends Tag
{

    public function getTag()
    {
        return 'do';
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $node = new RootNode();
        do {
            $node->addChild(new ExpressionNode($parser->parseExpression($stream)));
        } while ($stream->current()->test(Token::PUNCTUATION, ','));

        return $node;
    }
}
