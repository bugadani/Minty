<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Operators;

use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\Nodes\OperatorNode;
use Modules\Templating\Compiler\Operator;

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
