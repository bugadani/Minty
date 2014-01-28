<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Nodes;

use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\Node;

class ArrayNode extends Node
{
    private $data;

    public function __construct()
    {
        $this->data = array();
    }

    public function add(Node $value, Node $key = null)
    {
        $this->data[] = array($value, $key);
    }

    public function compile(Compiler $compiler)
    {
        $compiler->add('array(');
        $first = true;
        foreach ($this->data as $item) {
            list($value, $key) = $item;
            if (!$first) {
                $compiler->add(', ');
            } else {
                $first = false;
            }
            if ($key !== null) {
                $key->compile($compiler);
                $compiler->add(' => ');
            }
            $value->compile($compiler);
        }
        $compiler->add(')');
    }
}
