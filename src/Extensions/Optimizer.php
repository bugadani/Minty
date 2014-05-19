<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Modules\Templating\Extensions;

use Modules\Templating\Compiler\NodeVisitors\ForLoopOptimizer;
use Modules\Templating\Extension;

class Optimizer extends Extension
{

    public function getExtensionName()
    {
        return 'optimizer';
    }

    public function getNodeVisitors()
    {
        $optimizers = array(
            new ForLoopOptimizer()
        );

        return $optimizers;
    }
}
