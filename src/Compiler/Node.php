<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler;

abstract class Node
{
    /**
     * @var Node
     */
    private $parent;

    /**
     * @param Node $parent
     */
    public function setParent($parent)
    {
        $this->parent = $parent;
    }

    /**
     * @return Node
     */
    public function getParent()
    {
        return $this->parent;
    }

    abstract public function compile(Compiler $compiler);
}
