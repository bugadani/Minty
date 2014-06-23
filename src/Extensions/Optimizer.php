<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Minty\Extensions;

use Minty\Compiler\NodeVisitors\EnvironmentVariableOptimizer;
use Minty\Compiler\NodeVisitors\ForLoopOptimizer;
use Minty\Extension;

class Optimizer extends Extension
{

    public function getExtensionName()
    {
        return 'optimizer';
    }

    public function getNodeVisitors()
    {
        return array(
            new ForLoopOptimizer(),
            new EnvironmentVariableOptimizer()
        );
    }
}
