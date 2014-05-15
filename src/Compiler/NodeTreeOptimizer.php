<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler;

class NodeTreeOptimizer
{
    /**
     * @var NodeOptimizer[]
     */
    private $optimizers = array();

    public function addOptimizer(NodeOptimizer $optimizer)
    {
        $this->optimizers[] = $optimizer;
    }

    public function optimize(Node $node)
    {
        $this->runOptimizers($node);
        foreach($node->getChildren() as $child) {
            $this->optimize($child);
        }
    }

    /**
     * @param Node $node
     */
    private function runOptimizers(Node $node)
    {
        foreach ($this->optimizers as $optimizer) {
            $optimizer->optimize($node);
        }
    }
}
