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

class BracketOperator extends ParenthesisOperators
{

    public function operators()
    {
        return array('[', ']');
    }

    protected function opening(Parser $parser, $operator)
    {
        $stream = $parser->getTokenStream();
        //TODO: ez nem egészen OK
        if ($stream->test(Token::IDENTIFIER) || $stream->test(Token::OPERATOR, ']')) {
            //array indexing
            $parser->pushToken(Token::OPERATOR, $operator);
            $parser->pushState(Parser::STATE_ARRAY, 'brackets');
            $stream->expect(Token::EXPRESSION_START);
            $stream->expect(Token::OPERATOR, '+');
            $stream->expect(Token::OPERATOR, '-');
            $stream->expect(Token::IDENTIFIER);
            $stream->expect(Token::LITERAL);
            $stream->expect(Token::STRING);
        } else {
            //array creation
            $expected_states = array(
                Parser::STATE_EXPRESSION,
                Parser::STATE_ASSIGNMENT,
                Parser::STATE_ARGUMENT_LIST
            );

            $has_expected_state = false;
            foreach ($expected_states as $expected) {
                if ($parser->isState($expected)) {
                    $has_expected_state = true;
                    break;
                }
            }

            $expected_tokens = array(
                Token::OPERATOR,
                Token::KEYWORD,
                Token::EXPRESSION_START,
                Token::ARGUMENT_LIST_START
            );
            if ($has_expected_state && $stream->test($expected_tokens)) {
                $parser->pushState(Parser::STATE_ARGUMENT_LIST, 'array');
                $parser->pushToken(Token::ARGUMENT_LIST_START, 'array');
            } else {
                return false;
            }
        }
        return true;
    }

    protected function closing(Parser $parser, $operator)
    {
        if ($parser->isState(Parser::STATE_EXPRESSION, 'implicit', true)) {
            $parser->pushToken(Token::EXPRESSION_END, 'implicit');
        }
        if ($parser->isState(Parser::STATE_ARRAY)) {
            $parser->pushToken(Token::OPERATOR, $operator);
        } elseif ($parser->isState(Parser::STATE_ARGUMENT_LIST, 'array')) {
            $parser->pushToken(Token::ARGUMENT_LIST_END, 'array');
        } else {
            return false;
        }
        $parser->popState();
        return true;
    }
}
