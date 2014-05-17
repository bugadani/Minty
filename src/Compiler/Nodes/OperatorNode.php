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
    const OPERAND_MIDDLE = 2;

    /**
     * @var Operator
     */
    private $operator;

    public function __construct(Operator $operator)
    {
        $this->operator = $operator;
    }

    public function addOperand($type, Node $value = null)
    {
        $this->addChild($value, $type);
    }

    public function hasOperand($type)
    {
        return $this->hasChild($type);
    }

    /**
     * @param $type
     *
     * @return Node
     * @throws SyntaxException
     */
    public function getOperand($type)
    {
        if (!$this->hasChild($type)) {
            throw new SyntaxException('Operator has a missing operand.');
        }

        return $this->getChild($type);
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
