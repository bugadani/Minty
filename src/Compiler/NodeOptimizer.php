<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler;

abstract class NodeOptimizer
{
    public function nodeHasChild(Node $node, $child)
    {
        if (is_string($child)) {
            return $this->nodeHasChildByClassName($node, $child);
        } elseif (is_callable($child)) {
            return $this->nodeHasChildByCallback($node, $child);
        }
        throw new \InvalidArgumentException('$child must be a string or a callback');
    }

    public function nodeHasParent(Node $node, $parent)
    {
        if (is_string($parent)) {
            return $this->nodeHasParentByClassName($node, $parent);
        } elseif (is_callable($parent)) {
            return $this->nodeHasParentByCallback($node, $parent);
        }
        throw new \InvalidArgumentException('$parent must be a string or a callback');
    }

    abstract public function optimize(Node $node);

    private function nodeHasChildByClassName(Node $node, $class)
    {
        foreach($node->getChildren() as $child) {
            if($child instanceof $class) {
                return true;
            }
            if($this->nodeHasChildByClassName($child, $class)) {
                return true;
            }
        }

        return false;
    }

    private function nodeHasChildByCallback(Node $node, $callback)
    {
        foreach ($node->getChildren() as $child) {
            if ($callback($child)) {
                return true;
            }
            if ($this->nodeHasChildByCallback($child, $callback)) {
                return true;
            }
        }

        return false;
    }

    private function nodeHasParentByClassName(Node $node, $class)
    {
        $current = $node;
        while($current->getParent() !== null) {
            $current = $current->getParent();
            if($current instanceof $class) {
                return true;
            }
        }
        return false;
    }

    private function nodeHasParentByCallback(Node $node, $callback)
    {
        $current = $node;
        while ($current->getParent() !== null) {
            $current = $current->getParent();
            if ($callback($node)) {
                return true;
            }
        }

        return false;
    }
}
