<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Operators\LogicOperators;

use Minty\Compiler\Operators\SimpleBinaryOperator;

class XorOperator extends SimpleBinaryOperator
{

    public function operators()
    {
        return 'xor';
    }

    public function compileOperator()
    {
        return ' xor ';
    }
}
