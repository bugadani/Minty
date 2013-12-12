<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Operators\ArithmeticOperators;

use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\Nodes\OperatorNode;
use Modules\Templating\Compiler\Operator;

class DivisionOperator extends Operator
{

    public function operators()
    {
        return '/';
    }

    public function compile(Compiler $compiler, OperatorNode $node)
    {
        $compiler->add('(');
        $node->getOperand(OperatorNode::OPERAND_LEFT)->compile($compiler);
        $compiler->add(' / ');
        $node->getOperand(OperatorNode::OPERAND_RIGHT)->compile($compiler);
        $compiler->add(')');
    }
}
