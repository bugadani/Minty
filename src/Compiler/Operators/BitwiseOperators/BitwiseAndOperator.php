<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Operators\BitwiseOperators;

use Minty\Compiler\Operators\SimpleBinaryOperator;

class BitwiseAndOperator extends SimpleBinaryOperator
{

    public function operators()
    {
        return 'b-and';
    }

    protected function compileOperator()
    {
        return ' & ';
    }
}
