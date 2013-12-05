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
use Modules\Templating\Compiler\TemplateCompiler;
use Modules\Templating\Compiler\Token;

class ArithmeticOperators extends Operator
{

    public function operators()
    {
        return array('/', '*', '%', '+', '-', '^', '..', '...');
    }

    public function parseOperator(Parser $parser, $operator)
    {
        $stream = $parser->getTokenStream();

        $binary = array(
            Token::EXPRESSION_END,
            Token::ARGUMENT_LIST_END,
            Token::IDENTIFIER,
            Token::LITERAL,
        );
        if ($stream->test($binary)) {
            $parser->pushToken(Token::OPERATOR, $operator);
            $not_sign = function ($operator) {
                return !($operator == '+' || $operator == '-');
            };
            $stream->expect(Token::EXPRESSION_END, null, 1, true)->also(Token::OPERATOR, $not_sign, 1, true);
            return true;
        }
    }
}
