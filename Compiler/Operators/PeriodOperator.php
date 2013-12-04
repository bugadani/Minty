<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Operators;

use Modules\Templating\Compiler\Operator;
use Modules\Templating\Compiler\Token;
use Modules\Templating\Compiler\Parser;

class PeriodOperator extends Operator
{

    public function operators()
    {
        return '.';
    }

    public function parseOperator(Parser $parser, $operator)
    {
        $stream   = $parser->getTokenStream();
        $in_state = $parser->isState(Parser::STATE_EXPRESSION);
        $in_state |= $parser->isState(Parser::STATE_ARGUMENT_LIST);


        if (!$in_state) {
            return false;
        }
        if ($stream->test(Token::IDENTIFIER)) {
            $parser->pushToken(Token::OPERATOR, $operator);
        } elseif ($stream->test(Token::LITERAL)) {
            $parser->pushToken(Token::OPERATOR, $operator);
            $parser->pushState(Parser::STATE_POSSIBLE_FLOAT);
        } else {
            return false;
        }
        $stream->expect(Token::EXPRESSION_END, '}', 1, true);

        return true;
    }
}
