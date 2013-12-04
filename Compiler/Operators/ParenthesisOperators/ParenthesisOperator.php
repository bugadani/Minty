<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Operators\ParenthesisOperators;

use Modules\Templating\Compiler\Operators\ParenthesisOperators;
use Modules\Templating\Compiler\Parser;
use Modules\Templating\Compiler\Token;

class ParenthesisOperator extends ParenthesisOperators
{

    public function operators()
    {
        return array('(', ')');
    }

    protected function opening(Parser $parser, $operator)
    {
        $stream = $parser->getTokenStream();
        if ($stream->test(array(Token::IDENTIFIER, Token::TEST))) {
            //function or method call
            $parser->pushState(Parser::STATE_ARGUMENT_LIST, 'method');
            $parser->pushToken(Token::ARGUMENT_LIST_START, 'args');
        } elseif ($stream->test(Token::OPERATOR, '->')) {
            //method call - this is an error, method name is missing
            return false;
        } else {
            //expression grouping
            $parser->pushState(Parser::STATE_EXPRESSION, $operator);
            $parser->pushToken(Token::EXPRESSION_START, $operator);
        }
        return true;
    }

    protected function closing(Parser $parser, $operator)
    {
        $return = false;
        $stream = $parser->getTokenStream();
        if ($parser->isState(Parser::STATE_EXPRESSION, '(', true)) {
            if ($stream->test(Token::EXPRESSION_START)) {
                $parser->throwException('unexpected', 'closing parenthesis');
            }
            $parser->pushToken(Token::EXPRESSION_END, $operator);
            $return = true;
        }
        /* if ($parser->isState(Parser::STATE_EXPRESSION, 'implicit', true)) {

          $parser->pushToken(Token::EXPRESSION_END, 'implicit');
          $return = true;
          } */
        if ($parser->isState(Parser::STATE_ARGUMENT_LIST, 'method', true)) {

            $parser->pushToken(Token::ARGUMENT_LIST_END, 'args');
            $return = true;
        }
        return $return;
    }
}
