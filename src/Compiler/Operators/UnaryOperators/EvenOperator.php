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

class EvenOperator extends Operator
{

    public function operators()
    {
        return ['is not odd', 'is even'];
    }

    public function compile(Compiler $compiler, OperatorNode $node)
    {
        $compiler
            ->add('(')
            ->compileNode($node->getChild(OperatorNode::OPERAND_LEFT))
            ->add(' % 2 == 0)');
    }
}
