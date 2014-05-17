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
     * @var Node[]
     */
    private $children = array();

    /**
     * @param Node $parent
     */
    public function setParent(Node $parent)
    {
        $this->parent = $parent;
        $parent->addChild($this);
    }

    /**
     * @return Node
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @param Node $node
     * @param null $key
     */
    public function addChild(Node $node, $key = null)
    {
        if ($key === null) {
            $this->children[] = $node;
        } else {
            $this->children[$key] = $node;
        }
    }

    /**
     * @return Node[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    public function hasChild($key)
    {
        return isset($this->children[$key]);
    }

    public function getChild($key)
    {
        return $this->children[$key];
    }

    abstract public function compile(Compiler $compiler);
}
