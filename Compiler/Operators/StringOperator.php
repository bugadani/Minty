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

class StringOperator extends Operator
{

    public function operators()
    {
        return array('"', "'");
    }

    public function parseOperator(Parser $parser, $operator)
    {
        $value = array(
            'string'    => '',
            'delimiter' => $operator
        );
        $parser->pushState(Parser::STATE_STRING, $value);
        return true;
    }
}
