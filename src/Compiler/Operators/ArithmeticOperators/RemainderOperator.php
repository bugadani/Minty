<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Operators\ArithmeticOperators;

use Minty\Compiler\Operators\SimpleBinaryOperator;

class RemainderOperator extends SimpleBinaryOperator
{

    public function operators()
    {
        return '%';
    }

    public function compileOperator()
    {
        return ' % ';
    }
}
