<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Operators\ExistenceOperators;

use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\Nodes\ArrayIndexNode;
use Modules\Templating\Compiler\Nodes\FunctionNode;
use Modules\Templating\Compiler\Nodes\IdentifierNode;
use Modules\Templating\Compiler\Nodes\OperatorNode;
use Modules\Templating\Compiler\Nodes\VariableNode;
use Modules\Templating\Compiler\Operator;
use Modules\Templating\Compiler\Operators\PropertyAccessOperator;

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
        } elseif ($operand instanceof OperatorNode && $operand->getOperator() instanceof PropertyAccessOperator) {
            $right = $operand->getChild(OperatorNode::OPERAND_RIGHT);
            $left = $operand->getChild(OperatorNode::OPERAND_LEFT);

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
