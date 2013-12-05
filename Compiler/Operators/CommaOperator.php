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

class CommaOperator extends Operator
{

    public function operators()
    {
        return ',';
    }

    public function parseOperator(Parser $parser, $operator)
    {
        $stream = $parser->getTokenStream();
        if ($stream->test(Token::EXPRESSION_START)) {
            return false;
        }
        if ($stream->test(Token::ARGUMENT_LIST_START)) {
            return false;
        }
        if ($parser->isState(Parser::STATE_ARGUMENT_LIST)) {
            $parser->pushToken(Token::OPERATOR, $operator);
            return true;
        }
        return false;
    }
}
