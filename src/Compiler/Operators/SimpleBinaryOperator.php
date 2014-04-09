<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Operators;

use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\Nodes\OperatorNode;
use Modules\Templating\Compiler\Operator;

abstract class SimpleBinaryOperator extends Operator
{

    public function compile(Compiler $compiler, OperatorNode $node)
    {
        $compiler
            ->add('(')
            ->compileNode($node->getOperand(OperatorNode::OPERAND_LEFT))
            ->add($this->compileOperator())
            ->compileNode($node->getOperand(OperatorNode::OPERAND_RIGHT))
            ->add(')');
    }

    abstract protected function compileOperator();
}
