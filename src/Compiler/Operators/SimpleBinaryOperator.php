<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Operators;

use Minty\Compiler\Compiler;
use Minty\Compiler\Nodes\OperatorNode;
use Minty\Compiler\Operator;

abstract class SimpleBinaryOperator extends Operator
{

    public function compile(Compiler $compiler, OperatorNode $node)
    {
        $compiler
            ->add('(')
            ->compileNode($node->getChild(OperatorNode::OPERAND_LEFT))
            ->add($this->compileOperator())
            ->compileNode($node->getChild(OperatorNode::OPERAND_RIGHT))
            ->add(')');
    }

    abstract protected function compileOperator();
}
