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

class PropertyAccessOperator extends Operator
{

    public function operators()
    {
        return '.';
    }

    public function compile(Compiler $compiler, OperatorNode $node)
    {
        $left = $node->getChild(OperatorNode::OPERAND_LEFT);
        $keys = [$node->getChild(OperatorNode::OPERAND_RIGHT)];

        while ($this->isPropertyAccessOperator($left)) {
            /** @var $left OperatorNode */
            $keys[] = $left->getChild(OperatorNode::OPERAND_RIGHT);
            $left   = $left->getChild(OperatorNode::OPERAND_LEFT);
        }
        $arguments = [$left, array_reverse($keys)];

        if ($node->hasChild(OperatorNode::OPERAND_MIDDLE)) {
            $arguments[] = $node->getChild(OperatorNode::OPERAND_MIDDLE);
        }
        $compiler
            ->add('$context->' . $this->getMethodName($node))
            ->compileArgumentList($arguments);
    }

    /**
     * @param OperatorNode $node
     *
     * @return string
     */
    private function getMethodName(OperatorNode $node)
    {
        if ($node->hasData('mode')) {
            $mode = $node->getData('mode');
        } else {
            $mode = 'get';
        }

        switch ($mode) {
            case 'has':
                return 'hasProperty';

            default:
            case 'get':
                return 'getProperty';

            case 'set':
                return 'setProperty';
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
