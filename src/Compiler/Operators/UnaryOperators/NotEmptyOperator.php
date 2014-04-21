<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Operators\UnaryOperators;

class NotEmptyOperator extends EmptyOperator
{

    public function operators()
    {
        return 'is not empty';
    }

    protected function getFunctionName()
    {
        return '!' . parent::getFunctionName();
    }
}
