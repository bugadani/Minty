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
use Modules\Templating\Compiler\Operator;

class UnaryOperatorNode extends Node
{
    private $operator;
    private $operand_one;
    private $operand_other;

    public function __construct(Operator $operator, $one, $other = null)
    {
        $this->operator      = $operator;
        $this->operand_one   = $one;
        $this->operand_other = $other;
    }

    public function compile(Compiler $compiler)
    {
        $this->operator->compile($compiler, $this->operand_one, $this->operand_other);
    }
}
