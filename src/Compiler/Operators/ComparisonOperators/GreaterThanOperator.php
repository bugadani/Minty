<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Operators\ComparisonOperators;

use Minty\Compiler\Operators\SimpleBinaryOperator;

class GreaterThanOperator extends SimpleBinaryOperator
{

    public function operators()
    {
        return array('>', 'is greater than');
    }

    public function compileOperator()
    {
        return ' > ';
    }
}
