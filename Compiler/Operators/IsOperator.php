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

class IsOperator extends Operator
{

    public function operators()
    {
        return 'is';
    }

    public function parseOperator(Parser $parser, $operator)
    {
        $stream = $parser->getTokenStream();

        $expected = array(
            Token::IDENTIFIER,
            Token::EXPRESSION_END,
            Token::ARGUMENT_LIST_END,
            Token::LITERAL,
            Token::STRING
        );

        if ($stream->test($expected)) {

            $parser->pushToken(Token::OPERATOR, 'is');
            $stream->expect(Token::TEST);
            $stream->expect(Token::OPERATOR, '!')->then(Token::TEST);

            return true;
        }
        return false;
    }
}
