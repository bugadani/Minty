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

    abstract public function getPriority();

    abstract public function enterNode(Node $node);

    abstract public function leaveNode(Node $node);
}
