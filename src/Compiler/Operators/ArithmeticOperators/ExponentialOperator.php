<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Operators\ArithmeticOperators;

use Minty\Compiler\Operators\FunctionOperator;

class ExponentialOperator extends FunctionOperator
{

    public function operators()
    {
        return array('^', '**');
    }

    protected function getFunctionName()
    {
        return 'pow';
    }
}
