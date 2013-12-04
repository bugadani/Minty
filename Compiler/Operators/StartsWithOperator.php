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

class StartsWithOperator extends Operator
{

    public function operators()
    {
        return array('starts with', 'does not start with');
    }

    public function parseOperator(Parser $parser, $operator)
    {
        $stream = $parser->getTokenStream();

        $expectations = array(
            Token::STRING,
            Token::IDENTIFIER,
            Token::EXPRESSION_END,
            Token::ARGUMENT_LIST_END,
            array(Token::OPERATOR, ']')
        );
        if (!$stream->test($expectations)) {
            return false;
        }
        $parser->pushToken(Token::OPERATOR, $operator);
        $stream->expect(Token::STRING);
        $stream->expect(Token::IDENTIFIER);
        return true;
    }
}
