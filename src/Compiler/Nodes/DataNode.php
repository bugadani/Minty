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

class DataNode extends Node
{
    private $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function compile(Compiler $compiler)
    {
        $compiler->compileData($this->value);
    }

    public function getValue()
    {
        return $this->value;
    }

    public function stringify()
    {
        if (is_bool($this->value)) {
            return ($this->value ? 'true' : 'false');
        } elseif (is_scalar($this->value)) {
            return $this->value;
        } elseif (is_array($this->value)) {
            $count = count($this->value);

            return "array [{$count}]";
        } else {
            return 'object';
        }
    }
}
