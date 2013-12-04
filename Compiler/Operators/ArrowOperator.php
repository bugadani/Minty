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

class ArrowOperator extends Operator
{

    public function operators()
    {
        return '->';
    }

    public function parseOperator(Parser $parser, $operator)
    {
        $stream = $parser->getTokenStream();

        $excluded = array(
            Token::LITERAL,
            Token::STRING
        );
        if ($stream->test($excluded)) {
            return false;
        }
        $parser->pushToken(Token::OPERATOR, $operator);
        $stream->expect(Token::IDENTIFIER);
        return true;
    }
}
