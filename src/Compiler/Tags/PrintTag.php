<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Tags;

use Minty\Compiler\Nodes\ExpressionNode;
use Minty\Compiler\Nodes\PrintNode;
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
        if (!$stream->current()->test(Token::PUNCTUATION, ':')) {
            return new PrintNode($expression);
        }

        return new ExpressionNode(
            $parser->getEnvironment()
                ->getBinaryOperators()
                ->getOperator(':')
                ->createNode(
                    $expression,
                    $parser->parseExpression($stream)
                )
        );
    }
}
