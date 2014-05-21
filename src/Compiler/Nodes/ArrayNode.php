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
    private $itemCount = 0;

    /**
     * @var Node[]
     */
    private $keys = array();

    /**
     * @var Node[]
     */
    private $values = array();

    public function add(Node $value, Node $key = null)
    {
        $this->values[] = $value;
        $this->keys[]   = $key;
        ++$this->itemCount;
    }

    public function compile(Compiler $compiler)
    {
        $compiler->add('array(');
        for ($i = 0; $i < $this->itemCount; ++$i) {
            if ($i !== 0) {
                $compiler->add(', ');
            }
            if ($this->keys[$i] !== null) {
                $this->keys[$i]->compile($compiler);
                $compiler->add(' => ');
            }
            $this->values[$i]->compile($compiler);
        }
        $compiler->add(')');
    }
}
