<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Nodes;

use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\Node;

class RootNode extends Node
{
    public function compile(Compiler $compiler)
    {
        foreach ($this->getChildren() as $node) {
            $compiler->compileNode($node);
        }
    }
}
