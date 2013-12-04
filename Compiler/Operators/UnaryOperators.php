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

abstract class UnaryOperators extends Operator
{

    public function parseOperator(Parser $parser, $operator)
    {
        $pre    = array(
            Token::EXPRESSION_START,
            Token::ARGUMENT_LIST_START,
            Token::OPERATOR
        );
        $post   = array(
            Token::IDENTIFIER,
        );
        $stream = $parser->getTokenStream();
        if ($stream->test($pre)) {
            return $this->pre($parser, $operator);
        } elseif ($stream->test($post)) {
            return $this->post($parser, $operator);
        } else {
            return false;
        }
    }

    protected function pre(Parser $parser, $operator)
    {
        $parser->pushToken(Token::OPERATOR, $operator);
        return true;
    }

    protected function post(Parser $parser, $operator)
    {
        $parser->pushToken(Token::OPERATOR, $operator);
        return true;
    }
}
