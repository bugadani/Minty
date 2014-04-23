<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Operators\TestOperators;

class NotDivisibleByOperator extends DivisibleByOperator
{

    public function operators()
    {
        return 'is not divisible by';
    }

    public function getFunctionName()
    {
        return '!' . parent::getFunctionName();
    }
}
