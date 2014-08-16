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

class IdentifierNode extends Node
{
    public function __construct($name)
    {
        parent::__construct(['name' => $name]);
    }

    public function compile(Compiler $compiler)
    {
        $compiler->compileString($this->getData('name'));
    }
}
