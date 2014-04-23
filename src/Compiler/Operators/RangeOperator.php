<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Operators;

class RangeOperator extends FunctionOperator
{

    public function operators()
    {
        return '..';
    }

    protected function getFunctionName()
    {
        return 'range';
    }
}
