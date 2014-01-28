<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Operators\ComparisonOperators;

use Modules\Templating\Compiler\Operators\SimpleBinaryOperator;

class NotEqualsOperator extends SimpleBinaryOperator
{

    public function operators()
    {
        return array('!=', '<>', 'is not', 'does not equal');
    }

    public function compileOperator()
    {
        return ' != ';
    }
}
