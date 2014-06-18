<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler;

abstract class Node
{
    /**
     * @var array
     */
    private $data = array();

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
     *
     * @return Node
     */
    public function addChild(Node $node, $key = null)
    {
        if ($key === null) {
            $this->children[] = $node;
        } else {
            $this->children[$key] = $node;
        }

        return $node;
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

    public function removeChild($key)
    {
        unset($this->children[$key]->parent);
        unset($this->children[$key]);
    }

    abstract public function compile(Compiler $compiler);

    public function setData(array $data)
    {
        $this->data = $data;
    }

    public function addData($key, $value)
    {
        if ($key === null) {
            $this->data[] = $value;
        } else {
            $this->data[$key] = $value;
        }
    }

    public function getData($key)
    {
        return $this->data[$key];
    }

    public function hasData($key)
    {
        return isset($this->data[$key]);
    }
}
