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

class ColonOperator extends Operator
{

    public function operators()
    {
        return ':';
    }

    public function parseOperator(Parser $parser, $operator)
    {
        $stream = $parser->getTokenStream();

        $expected = array(
            array(Token::IDENTIFIER),
            array(Token::STRING),
            array(Token::LITERAL, 'is_numeric'),
        );

        $last_token_expected = false;
        foreach ($expected as $token) {
            $last_token_expected |= $stream->test($token);
        }
        if (!$last_token_expected) {
            return false;
        }

        if (!$parser->isState(Parser::STATE_ARGUMENT_LIST)) {
            return false;
        }

        $token = $stream->pop();
        if ($token->test(Token::LITERAL, 'is_numeric')) {
            $type = Token::LITERAL;
        } else {
            $type = Token::STRING;
        }

        $parser->pushToken($type, $token->getValue());
        $parser->pushToken(Token::OPERATOR, '=>');
        return true;
    }
}
