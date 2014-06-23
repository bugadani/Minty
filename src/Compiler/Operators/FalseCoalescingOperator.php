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
use Minty\Compiler\Nodes\VariableNode;
use Minty\Compiler\Operator;

class FalseCoalescingOperator extends Operator
{

    public function operators()
    {
        return '??';
    }

    public function compile(Compiler $compiler, OperatorNode $node)
    {
        $left = $node->getChild(OperatorNode::OPERAND_LEFT);

        $compiler->add('(');
        if ($left instanceof VariableNode) {
            $compiler
                ->add('isset(')
                ->compileNode($left)
                ->add(') && (')
                ->compileNode($left)
                ->add(')) ? (')
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
}
