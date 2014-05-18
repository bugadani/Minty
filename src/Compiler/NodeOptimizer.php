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

    abstract public function getPriority();

    abstract public function enterNode(Node $node);

    abstract public function leaveNode(Node $node);
}
