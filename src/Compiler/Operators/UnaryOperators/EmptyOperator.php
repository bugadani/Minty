<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Operators\UnaryOperators;

use Modules\Templating\Compiler\Operators\FunctionOperator;

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
