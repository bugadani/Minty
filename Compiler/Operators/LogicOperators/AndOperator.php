<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Operators\LogicOperators;

use Modules\Templating\Compiler\Operators\SimpleBinaryOperator;

class AndOperator extends SimpleBinaryOperator
{

    public function operators()
    {
        return array('&&', 'and');
    }

    public function compileOperator()
    {
        return ' && ';
    }
}
