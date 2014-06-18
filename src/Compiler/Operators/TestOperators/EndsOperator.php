<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Operators\TestOperators;

use Minty\Compiler\Operators\FunctionOperator;

class EndsOperator extends FunctionOperator
{

    public function operators()
    {
        return 'ends with';
    }

    protected function getFunctionName()
    {
        return 'ends';
    }
}
