<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Operators\TestOperators;

use Modules\Templating\Compiler\Operators\FunctionOperator;

class StartsOperator extends FunctionOperator
{

    public function operators()
    {
        return 'starts with';
    }

    protected function getFunctionName()
    {
        return 'starts';
    }
}
