<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Operators\ComparisonOperators;

use Minty\Compiler\Operators\SimpleBinaryOperator;

class NotIdenticalOperator extends SimpleBinaryOperator
{

    public function operators()
    {
        return array('!==', 'is not identical', 'is not same as');
    }

    public function compileOperator()
    {
        return ' !== ';
    }
}
