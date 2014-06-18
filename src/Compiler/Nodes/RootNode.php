<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Nodes;

use Minty\Compiler\Compiler;
use Minty\Compiler\Node;

class RootNode extends Node
{
    public function compile(Compiler $compiler)
    {
        foreach ($this->getChildren() as $node) {
            $compiler->compileNode($node);
        }
    }
}
