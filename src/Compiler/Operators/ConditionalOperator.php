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

class ConditionalOperator extends Operator
{

    public function __construct()
    {

    }

    public function operators()
    {
        throw new \BadMethodCallException('Conditional operator is handled differently.');
    }

    public function compile(Compiler $compiler, OperatorNode $node)
    {
        $compiler
            ->add('((')
            ->compileNode($node->getChild(OperatorNode::OPERAND_LEFT))
            ->add(') ?');

        if ($node->hasChild(OperatorNode::OPERAND_MIDDLE)) {
            $compiler
                ->add(' (')
                ->compileNode($node->getChild(OperatorNode::OPERAND_MIDDLE))
                ->add(') ');
        }

        $compiler
            ->add(': (')
            ->compileNode($node->getChild(OperatorNode::OPERAND_RIGHT))
            ->add('))');
    }
}
