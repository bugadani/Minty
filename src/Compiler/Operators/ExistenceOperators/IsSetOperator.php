<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Operators\ExistenceOperators;

use Minty\Compiler\Compiler;
use Minty\Compiler\Node;
use Minty\Compiler\Nodes\OperatorNode;
use Minty\Compiler\Operator;
use Minty\Compiler\Operators\PropertyAccessOperator;

class IsSetOperator extends Operator
{

    public function operators()
    {
        return 'is set';
    }

    public function compile(Compiler $compiler, OperatorNode $node)
    {
        $operand = $node->getChild(OperatorNode::OPERAND_LEFT);
        if ($this->isPropertyAccessOperator($operand)) {
            $operand->addData('mode', 'has');
            $operand->compile($compiler);
        } else {
            $compiler
                ->add('isset(')
                ->compileNode($operand)
                ->add(')');
        }
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
