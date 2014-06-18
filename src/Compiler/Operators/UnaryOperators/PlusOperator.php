<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Operators\UnaryOperators;

use Minty\Compiler\Compiler;
use Minty\Compiler\Nodes\OperatorNode;
use Minty\Compiler\Operator;

class PlusOperator extends Operator
{

    public function operators()
    {
        return '+';
    }

    public function compile(Compiler $compiler, OperatorNode $node)
    {
        $compiler
            ->add('(+')
            ->compileNode($node->getChild(OperatorNode::OPERAND_RIGHT))
            ->add(')');
    }
}
