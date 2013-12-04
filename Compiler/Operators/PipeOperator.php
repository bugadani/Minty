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

class PipeOperator extends Operator
{

    public function operators()
    {
        return '|';
    }

    public function parseOperator(Parser $parser, $operator)
    {
        $expected_states = array(
            Parser::STATE_EXPRESSION,
            Parser::STATE_ARGUMENT_LIST,
            Parser::STATE_ARRAY
        );

        $has_expected_state = false;
        foreach ($expected_states as $state) {
            if ($parser->isState($state)) {
                $has_expected_state = true;
                break;
            }
        }

        if (!$has_expected_state) {
            return false;
        } elseif ($parser->getTokenStream()->test(Token::EXPRESSION_START)) {
            return false;
        }
        $parser->pushToken(Token::OPERATOR, $operator);
        return true;
    }
}
