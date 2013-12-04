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

abstract class TestOperator extends Operator
{

    public function hasArguments()
    {
        return false;
    }

    public function getTestFunction($operator)
    {
        return 'is' . ucfirst($operator);
    }

    public function parseOperator(Parser $parser, $operator)
    {
        $stream = $parser->getTokenStream();

        $expected = array(
            Token::IDENTIFIER,
            Token::EXPRESSION_END,
            Token::ARGUMENT_LIST_END,
            array(Token::OPERATOR, '!')
        );

        if ($stream->test($expected)) {
            $parser->pushToken(Token::TEST, $this->getTestFunction($operator));
            if ($this->hasArguments()) {
                $stream->expect(Token::ARGUMENT_LIST_START, 'args');
            } else {
                $stream->expect(Token::ARGUMENT_LIST_START, 'args', 1, true);
            }
            return true;
        }
        return false;
    }
}
