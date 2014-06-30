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
    private $data;

    /**
     * @var Node[]
     */
    private $children = array();

    public function __construct(array $data = array())
    {
        $this->data = $data;
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
        unset($this->children[$key]);
    }

    abstract public function compile(Compiler $compiler);

    public function setData(array $data)
    {
        $this->data = $data;
    }

    public function addData($key, $value)
    {
        $this->data[$key] = $value;
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
