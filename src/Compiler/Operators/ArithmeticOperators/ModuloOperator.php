<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Operators\ArithmeticOperators;

use Minty\Compiler\Compiler;
use Minty\Compiler\Nodes\OperatorNode;
use Minty\Compiler\Operator;

class ModuloOperator extends Operator
{

    public function operators()
    {
        return 'mod';
    }

    public function compile(Compiler $compiler, OperatorNode $node)
    {
        //if(sign($left) != sign($right))
        $compiler
            ->add('((')
            ->compileNode($node->getChild(OperatorNode::OPERAND_LEFT))
            ->add(' < 0 && ')
            ->compileNode($node->getChild(OperatorNode::OPERAND_RIGHT))
            ->add(' > 0) || (')
            ->compileNode($node->getChild(OperatorNode::OPERAND_RIGHT))
            ->add(' < 0 && ')
            ->compileNode($node->getChild(OperatorNode::OPERAND_LEFT))
            ->add(' > 0)');

        //then $right + $left % right
        $compiler
            ->add(' ? (')
            ->compileNode($node->getChild(OperatorNode::OPERAND_RIGHT))
            ->add(' + ')
            ->compileNode($node->getChild(OperatorNode::OPERAND_LEFT))
            ->add(' % ')
            ->compileNode($node->getChild(OperatorNode::OPERAND_RIGHT))
            ->add(')');

        //else $left % $right
        $compiler
            ->add(': (')
            ->compileNode($node->getChild(OperatorNode::OPERAND_LEFT))
            ->add(' % ')
            ->compileNode($node->getChild(OperatorNode::OPERAND_RIGHT))
            ->add('))');
    }
}
