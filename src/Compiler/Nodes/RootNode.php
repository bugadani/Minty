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
    private $children;

    public function __construct()
    {
        $this->children = array();
    }

    public function addChild(Node $node)
    {
        $this->children[] = $node;
    }

    public function compile(Compiler $compiler)
    {
        foreach ($this->children as $node) {
            $node->compile($compiler);
        }
    }
}
