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
use Minty\Compiler\Nodes\ArrayIndexNode;
use Minty\Compiler\Nodes\OperatorNode;
use Minty\Compiler\Nodes\VariableNode;
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
            $root = $operand;
            while($this->isPropertyAccessOperator($root)) {
                $root = $root->getChild(OperatorNode::OPERAND_LEFT);
            }
            if($root instanceof VariableNode) {
                $compiler
                    ->add('isset(')
                    ->compileNode($root)
                    ->add(') && ');
            }
            $operand->addData('mode', 'has');
            $operand->compile($compiler);
        } elseif ($operand instanceof ArrayIndexNode) {
            $variable = $operand->getChild('identifier');
            $keys    = [$operand->getChild('key')];
            while ($variable instanceof ArrayIndexNode) {
                /** @var $right OperatorNode */
                $keys[] = $variable->getChild('key');
                $variable  = $variable->getChild('identifier');
            }
            $arguments = [$variable, array_reverse($keys)];
            $compiler
                ->add('isset(')
                ->compileNode($variable)
                ->add(') && $context->hasProperty')
                ->compileArgumentList($arguments);
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
