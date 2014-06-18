<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Operators\ExistenceOperators;

use Minty\Compiler\Compiler;
use Minty\Compiler\Nodes\ArrayIndexNode;
use Minty\Compiler\Nodes\FunctionNode;
use Minty\Compiler\Nodes\IdentifierNode;
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
        if ($operand instanceof VariableNode || $operand instanceof ArrayIndexNode) {
            $compiler->add('isset(')
                ->compileNode($operand)
                ->add(')');
        } elseif ($operand instanceof OperatorNode && $operand->getOperator(
            ) instanceof PropertyAccessOperator
        ) {
            $right = $operand->getChild(OperatorNode::OPERAND_RIGHT);
            $left  = $operand->getChild(OperatorNode::OPERAND_LEFT);

            if ($right instanceof FunctionNode) {
                $compiler->add('$this->hasMethod(');
            } else {
                $compiler->add('$context->hasProperty(');
            }

            $compiler
                ->compileNode($left)
                ->add(', ');

            if ($right instanceof IdentifierNode) {
                $compiler->compileString($right->getName());
            } else {
                $compiler->compileNode($right);
            }
            $compiler->add(')');
        } else {
            $compiler->add('(')
                ->compileNode($operand)
                ->add(' !== null)');
        }
    }
}
