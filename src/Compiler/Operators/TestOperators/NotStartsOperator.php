<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Operators\TestOperators;

class NotStartsOperator extends StartsOperator
{

    public function operators()
    {
        return array('does not start with', 'not starts with');
    }

    protected function getFunctionName()
    {
        return '!' . parent::getFunctionName();
    }
}
