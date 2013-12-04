<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Operators\TestOperators;

use Modules\Templating\Compiler\Operators\TestOperator;

class LikeOperator extends TestOperator
{

    public function hasArguments()
    {
        return true;
    }

    public function operators()
    {
        return 'like';
    }
}
