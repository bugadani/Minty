<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Operators\TestOperators;

class NotMatchesOperator extends MatchesOperator
{

    public function operators()
    {
        return array('does not match', 'is not like', 'not matches');
    }

    protected function getFunctionName()
    {
        return '!' . parent::getFunctionName();
    }
}
