<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Operators\TestOperators;

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
