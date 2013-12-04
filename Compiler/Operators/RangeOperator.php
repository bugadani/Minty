<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Operators;

use Modules\Templating\Compiler\Operator;
use Modules\Templating\Compiler\Parser;
use Modules\Templating\Compiler\Token;

class RangeOperator extends Operator
{

    public function operators()
    {
        return array('..', '...');
    }

    public function parseOperator(Parser $parser, $operator)
    {
        $stream = $parser->getTokenStream();

        $excluded = array(
            Token::OPERATOR
        );
        if ($stream->test($excluded)) {
            return false;
        }
        $parser->pushToken(Token::OPERATOR, $operator);
        if ($operator == '...') {
            $stream->expect(Token::LITERAL, 'is_numeric');
            $stream->expect(Token::IDENTIFIER);
            $stream->expect(Token::EXPRESSION_START);
        } else {
            $stream->expect(Token::LITERAL);
            $stream->expect(Token::STRING);
            $stream->expect(Token::IDENTIFIER);
            $stream->expect(Token::EXPRESSION_START);
        }
        return true;
    }
}
