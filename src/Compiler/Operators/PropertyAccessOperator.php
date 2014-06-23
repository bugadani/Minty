<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Operators;

use Minty\Compiler\Compiler;
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
        $compiler
            ->add('$context->' . $this->getMethodName($node) . '(')
            ->compileNode($node->getChild(OperatorNode::OPERAND_LEFT))
            ->add(', ')
            ->compileNode($node->getChild(OperatorNode::OPERAND_RIGHT))
            ->add(')');
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
