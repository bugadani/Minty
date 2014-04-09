<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Operators;

use BadMethodCallException;
use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\Nodes\OperatorNode;
use Modules\Templating\Compiler\Operator;

class ConditionalOperator extends Operator
{

    public function __construct()
    {

    }

    public function operators()
    {
        throw new BadMethodCallException('Conditional operator is handled differently.');
    }

    public function compile(Compiler $compiler, OperatorNode $node)
    {
        $compiler
            ->add('((')
            ->compileNode($node->getOperand(OperatorNode::OPERAND_LEFT))
            ->add(') ?');
        if ($node->hasOperand(2)) {
            $compiler
                ->add(' (')
                ->compileNode($node->getOperand(OperatorNode::OPERAND_MIDDLE))
                ->add(') ');
        }
        $compiler
            ->add(': (')
            ->compileNode($node->getOperand(OperatorNode::OPERAND_RIGHT))
            ->add('))');
    }
}
