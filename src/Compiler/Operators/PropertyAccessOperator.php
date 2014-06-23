<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Operators;

use Minty\Compiler\Compiler;
use Minty\Compiler\Nodes\FunctionNode;
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
        $object = $node->getChild(OperatorNode::OPERAND_LEFT);
        $right  = $node->getChild(OperatorNode::OPERAND_RIGHT);

        if ($right instanceof FunctionNode) {
            $right->setObject($object);
            $compiler->compileNode($right);
        } else {
            $compiler
                ->add('$context->' . $this->getMethodName($node) . '(')
                ->compileNode($object)
                ->add(', ')
                ->compileNode($right)
                ->add(')');
        }
    }

    /**
     * @param OperatorNode $node
     *
     * @return string
     */
    private function getMethodName(OperatorNode $node)
    {
        if ($node->hasData('existence') && $node->getData('existence') === true) {
            return 'hasProperty';
        }

        return 'getProperty';
    }
}
