<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Operators\ArithmeticOperators;

use Modules\Templating\Compiler\Operators\SimpleBinaryOperator;

class AdditionOperator extends SimpleBinaryOperator
{

    public function operators()
    {
        return '+';
    }

    protected function compileOperator()
    {
        return ' + ';
    }
}
