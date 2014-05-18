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

    /**
     * @param NodeOptimizer[] $optimizers
     */
    public function __construct(array $optimizers = array())
    {
        foreach($optimizers as $optimizer) {
            $this->addOptimizer($optimizer);
        }
    }

    public function addOptimizer(NodeOptimizer $optimizer)
    {
        $priority = $optimizer->getPriority();
        if (!isset($this->optimizers[$priority])) {
            $this->optimizers[$priority] = array();
        }
        $this->optimizers[$priority][] = $optimizer;
    }

    public function optimize(Node $node)
    {
        ksort($this->optimizers);
        foreach ($this->optimizers as $optimizers) {
            foreach ($optimizers as $optimizer) {
                $this->visitNode($node, $optimizer);
            }
        }
    }

    /**
     * @param Node          $node
     * @param NodeOptimizer $optimizer
     */
    private function visitNode(Node $node, NodeOptimizer $optimizer)
    {
        $optimizer->optimize($node);
        foreach ($node->getChildren() as $child) {
            $this->visitNode($child, $optimizer);
        }
    }
}
