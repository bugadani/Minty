<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\NodeVisitors;

use Minty\Compiler\Node;
use Minty\Compiler\Nodes\ClassNode;
use Minty\Compiler\Nodes\VariableNode;
use Minty\Compiler\NodeVisitor;

class SelfAccessOptimizer extends NodeVisitor
{
    /**
     * @var ClassNode
     */
    private $currentClass;
    private $selfAccessed;

    public function getPriority()
    {
        return 2;
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof ClassNode) {
            $this->currentClass = $node;
            $this->selfAccessed = false;
        } elseif ($node instanceof VariableNode && $node->getName() === '_self') {
            $this->selfAccessed = true;
        }
    }

    public function leaveNode(Node $node)
    {
        if ($node instanceof ClassNode) {
            $this->currentClass->addData('self_accessed', $this->selfAccessed);
        }

        return true;
    }
}
