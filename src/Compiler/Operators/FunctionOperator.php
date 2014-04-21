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

abstract class FunctionOperator extends Operator
{

    public function compile(Compiler $compiler, OperatorNode $node)
    {
        $compiler
            ->add($this->getFunctionName())
            ->add('(');

        if ($node->hasOperand(OperatorNode::OPERAND_LEFT)) {
            $compiler->compileNode($node->getOperand(OperatorNode::OPERAND_LEFT));

            if ($node->hasOperand(OperatorNode::OPERAND_RIGHT)) {
                $compiler
                    ->add(', ')
                    ->compileNode($node->getOperand(OperatorNode::OPERAND_RIGHT));
            }
        } elseif ($node->hasOperand(OperatorNode::OPERAND_RIGHT)) {
            $compiler->compileNode($node->getOperand(OperatorNode::OPERAND_RIGHT));
        }

        $compiler->add(')');
    }

    abstract protected function getFunctionName();
}
