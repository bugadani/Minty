<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Operators\ComparisonOperators;

use Minty\Compiler\Operators\SimpleBinaryOperator;

class GreaterThanOrEqualsOperator extends SimpleBinaryOperator
{

    public function operators()
    {
        return ['>=', 'is greater than or equals', 'is at least'];
    }

    public function compileOperator()
    {
        return ' >= ';
    }
}
