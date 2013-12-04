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

class ConcatenationOperator extends Operator
{

    public function operators()
    {
        return '~';
    }

    public function parseOperator(Parser $parser, $operator)
    {
        $parser->pushToken(Token::OPERATOR, '~');
        return true;
    }
}
