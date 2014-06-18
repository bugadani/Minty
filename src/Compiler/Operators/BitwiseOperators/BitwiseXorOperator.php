<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Operators\BitwiseOperators;

use Minty\Compiler\Operators\SimpleBinaryOperator;

class BitwiseXorOperator extends SimpleBinaryOperator
{

    public function operators()
    {
        return 'b-xor';
    }

    protected function compileOperator()
    {
        return ' ^ ';
    }
}
