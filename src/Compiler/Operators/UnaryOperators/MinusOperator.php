<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Operators\UnaryOperators;

use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\Nodes\OperatorNode;
use Modules\Templating\Compiler\Operator;

class MinusOperator extends Operator
{

    public function operators()
    {
        return '-';
    }

    public function compile(Compiler $compiler, OperatorNode $node)
    {
        $compiler
            ->add('(-')
            ->compileNode($node->getOperand(OperatorNode::OPERAND_RIGHT))
            ->add(')');
    }
}
