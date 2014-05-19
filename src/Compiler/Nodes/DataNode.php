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

class DataNode extends Node
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function compile(Compiler $compiler)
    {
        $compiler->compileData($this->data);
    }

    public function getData()
    {
        return $this->data;
    }
}
