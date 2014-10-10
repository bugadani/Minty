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
use Minty\Compiler\Nodes\ArrayIndexNode;
use Minty\Compiler\Nodes\OperatorNode;
use Minty\Compiler\Nodes\VariableNode;
use Minty\Compiler\Operator;

class NullCoalescingOperator extends Operator
{

    public function operators()
    {
        return '??';
    }

    public function compile(Compiler $compiler, OperatorNode $node)
    {
        $left = $node->getChild(OperatorNode::OPERAND_LEFT);

        $compiler->add('(');
        if ($this->isPropertyAccessOperator($left)) {
            $root = $left;
            while ($this->isPropertyAccessOperator($root)) {
                $root = $root->getChild(OperatorNode::OPERAND_LEFT);
            }
            if ($root instanceof VariableNode) {
                $compiler
                    ->add('isset(')
                    ->compileNode($root)
                    ->add(') &&');
            }
            $left->addData('mode', 'has');
            $left->compile($compiler);
            $left->addData('mode', 'get');
            $compiler
                ->add(') ? (')
                ->compileNode($left)
                ->add(')');
        } elseif ($left instanceof VariableNode) {
            $compiler
                ->add('isset(')
                ->compileNode($left)
                ->add(')) ? (')
                ->compileNode($left)
                ->add(')');
        } elseif ($left instanceof ArrayIndexNode) {
            $variable = $left->getChild('identifier');
            $keys = [$left->getChild('key')];
            while ($variable instanceof ArrayIndexNode) {
                /** @var $right OperatorNode */
                $keys[]   = $variable->getChild('key');
                $variable = $variable->getChild('identifier');
            }
            $arguments = [$variable, array_reverse($keys)];
            $compiler
                ->add('isset(')
                ->compileNode($variable)
                ->add(') && $context->hasProperty')
                ->compileArgumentList($arguments)
                ->add(') ? (')
                ->compileNode($left)
                ->add(')');
        } else {
            $compiler
                ->compileNode($left)
                ->add(') ?');
        }

        $compiler->add(' : (')
            ->compileNode($node->getChild(OperatorNode::OPERAND_RIGHT))
            ->add(')');
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
