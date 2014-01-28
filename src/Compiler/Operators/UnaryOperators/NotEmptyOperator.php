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

class NotEmptyOperator extends Operator
{

    public function operators()
    {
        return 'is not empty';
    }

    public function compile(Compiler $compiler, OperatorNode $node)
    {
        $compiler->add('!$this->isEmpty(');
        $node->getOperand(OperatorNode::OPERAND_LEFT)->compile($compiler);
        $compiler->add(')');
    }
}
