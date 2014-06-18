<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Operators\UnaryOperators;

use Minty\Compiler\Operators\FunctionOperator;

class EmptyOperator extends FunctionOperator
{

    public function operators()
    {
        return 'is empty';
    }

    protected function getFunctionName()
    {
        return 'empty';
    }
}
