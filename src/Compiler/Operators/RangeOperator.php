<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Operators;

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
