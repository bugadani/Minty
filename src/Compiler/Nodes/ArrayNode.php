<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Nodes;

use Minty\Compiler\Compiler;
use Minty\Compiler\Node;

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
        $value->setParent($this);

        if ($key) {
            $this->keys[$this->itemCount] = $key;
            $key->setParent($this);
        }

        ++$this->itemCount;
    }

    public function compile(Compiler $compiler)
    {
        $compiler->add('array(');
        for ($i = 0; $i < $this->itemCount; ++$i) {
            if ($i !== 0) {
                $compiler->add(', ');
            }
            if (isset($this->keys[$i])) {
                $compiler->compileNode($this->keys[$i]);
                $compiler->add(' => ');
            }
            $compiler->compileNode($this->values[$i]);
        }
        $compiler->add(')');
    }
}
