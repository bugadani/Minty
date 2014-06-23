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

    public function add(Node $value, Node $key = null)
    {
        $this->addChild($value, 'value_' . $this->itemCount);
        if ($key) {
            $this->addChild($key, 'key_' . $this->itemCount);
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
            if ($this->hasChild('key_' . $i)) {
                $compiler->compileNode($this->getChild('key_' . $i));
                $compiler->add(' => ');
            }
            $compiler->compileNode($this->getChild('value_' . $i));
        }
        $compiler->add(')');
    }
}
