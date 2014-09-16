<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Operators;

use Minty\Compiler\Compiler;
use Minty\Compiler\Node;
use Minty\Compiler\Nodes\OperatorNode;
use Minty\Compiler\Operator;

class SetOperator extends Operator
{
    public function operators()
    {
        return ':';
    }

    public function createNode(array $operands)
    {
        if ($this->isPropertyAccessOperator($operands[OperatorNode::OPERAND_LEFT])) {
            $left = $operands[OperatorNode::OPERAND_LEFT];
            $left->addData('mode', 'set');
            $left->addChild($operands[OperatorNode::OPERAND_RIGHT]);

            return $left;
        } else {
            return parent::createNode($operands);
        }
    }


    public function compile(Compiler $compiler, OperatorNode $node)
    {
        $compiler
            ->add('')
            ->compileNode($node->getChild(OperatorNode::OPERAND_LEFT))
            ->add('=')
            ->compileNode($node->getChild(OperatorNode::OPERAND_RIGHT));
    }

    /**
     * @param Node $operand
     *
     * @return bool
     */
    private function isPropertyAccessOperator(Node $operand)
    {
        if (!$operand instanceof OperatorNode) {
            return false;
        }

        return $operand->getOperator() instanceof PropertyAccessOperator;
    }
}
