<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Operators\BitwiseOperators;

use Minty\Compiler\Operators\SimpleBinaryOperator;

class BitwiseOrOperator extends SimpleBinaryOperator
{

    public function operators()
    {
        return 'b-or';
    }

    protected function compileOperator()
    {
        return ' | ';
    }
}
