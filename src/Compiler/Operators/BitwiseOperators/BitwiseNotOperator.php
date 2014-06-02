<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Operators\BitwiseOperators;

use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\Nodes\OperatorNode;
use Modules\Templating\Compiler\Operator;

class BitwiseNotOperator extends Operator
{

    public function operators()
    {
        return '~';
    }

    public function compile(Compiler $compiler, OperatorNode $node)
    {
        $compiler
            ->add('~')
            ->compileNode($node->getChild(OperatorNode::OPERAND_RIGHT));
    }
}
