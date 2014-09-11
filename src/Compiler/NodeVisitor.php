<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler;

abstract class NodeVisitor
{
    /**
     * @return int
     */
    abstract public function getPriority();

    /**
     * @param Node $node
     */
    abstract public function enterNode(Node $node);

    /**
     * @param Node $node
     *
     * @return bool Return false to remove $node from the node tree.
     */
    abstract public function leaveNode(Node $node);
}
