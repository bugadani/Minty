<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Nodes;

use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\Exceptions\SyntaxException;
use Modules\Templating\Compiler\Node;
use Modules\Templating\Compiler\Operator;

class OperatorNode extends Node
{
    const OPERAND_LEFT   = 0;
    const OPERAND_RIGHT  = 1;
    const OPERAND_MIDDLE = 1;

    /**
     * @var Operator
     */
    private $operator;
    private $operands;

    public function __construct(Operator $operator)
    {
        $this->operator = $operator;
        $this->operands = array();
    }

    public function addOperand($type, Node $value = null)
    {
        $this->operands[$type] = $value;
    }

    public function hasOperand($type)
    {
        return isset($this->operands[$type]);
    }

    public function getOperand($type)
    {
        if (!isset($this->operands[$type])) {
            throw new SyntaxException('Operator has a missing operand.');
        }
        return $this->operands[$type];
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
