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
use Minty\Compiler\Operator;

class OperatorNode extends Node
{
    const OPERAND_LEFT = 0;
    const OPERAND_RIGHT = 1;
    const OPERAND_MIDDLE = 2;

    /**
     * @var Operator
     */
    private $operator;

    public function __construct(Operator $operator)
    {
        $this->operator = $operator;
    }

    public function getOperator()
    {
        return $this->operator;
    }

    public function compile(Compiler $compiler)
    {
        $this->operator->compile($compiler, $this);
    }
}
