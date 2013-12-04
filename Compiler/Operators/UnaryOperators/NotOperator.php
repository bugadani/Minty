<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Operators\UnaryOperators;

use Modules\Templating\Compiler\Operators\UnaryOperators;
use Modules\Templating\Compiler\Parser;
use Modules\Templating\Compiler\Token;

class NotOperator extends UnaryOperators
{

    public function operators()
    {
        return array('not', '!');
    }

    public function parseOperator(Parser $parser, $operator)
    {
        if (parent::parseOperator($parser, $operator)) {
            return true;
        }
        $stream = $parser->getTokenStream();
        if ($stream->test(Token::OPERATOR, 'is')) {
            return $this->pre($parser, $operator);
        }
        return false;
    }

    protected function pre(Parser $parser, $operator)
    {
        $parser->pushToken(Token::OPERATOR, '!');
        return true;
    }

    protected function post(Parser $parser, $operator)
    {
        return false;
    }
}
