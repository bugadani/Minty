<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Operators\TestOperators;

class NotEndsOperator extends EndsOperator
{

    public function operators()
    {
        return array('not ends with', 'does not end with');
    }

    protected function getFunctionName()
    {
        return '!' . parent::getFunctionName();
    }
}
