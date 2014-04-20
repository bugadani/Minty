<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Operators;

use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\Nodes\FunctionNode;
use Modules\Templating\Compiler\Nodes\IdentifierNode;
use Modules\Templating\Compiler\Nodes\OperatorNode;
use Modules\Templating\Compiler\Operator;

class PropertyAccessOperator extends Operator
{

    public function operators()
    {
        return '.';
    }

    public function compile(Compiler $compiler, OperatorNode $node)
    {
        $object = $node->getOperand(OperatorNode::OPERAND_LEFT);
        $right  = $node->getOperand(OperatorNode::OPERAND_RIGHT);

        if ($right instanceof FunctionNode) {
            $right->setObject($object);
            $compiler->compileNode($right);
        } else {
            $compiler
                ->add('$this->getProperty(')
                ->compileNode($object)
                ->add(', ');
            if ($right instanceof IdentifierNode) {
                $compiler->add($compiler->string($right->getName()));
            } else {
                $compiler->compileNode($right);
            }
            $compiler->add(')');
        }
    }
}
