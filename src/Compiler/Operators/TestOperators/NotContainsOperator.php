<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Operators\TestOperators;

use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\Nodes\OperatorNode;
use Modules\Templating\Compiler\Operator;

class NotContainsOperator extends ContainsOperator
{

    public function operators()
    {
        return array('not in', 'not contains', 'does not contain');
    }

    protected function getFunctionName()
    {
        return '!' . parent::getFunctionName();
    }
}
