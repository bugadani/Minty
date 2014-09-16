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

class SetTag extends Tag
{

    public function getTag()
    {
        return 'set';
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $node = new RootNode();

        do {
            $left = $parser->parseExpression($stream);
            $stream->expectCurrent(Token::PUNCTUATION, ':');
            $right = $parser->parseExpression($stream);

            $setOperator = $parser->getEnvironment()->getBinaryOperators()->getOperator(':');
            $varNode     = $setOperator->createNode($left, $right);

            $node->addChild(new ExpressionNode($varNode));
        } while ($stream->current()->test(Token::PUNCTUATION, ','));

        return $node;
    }
}
