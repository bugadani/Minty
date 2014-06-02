<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Operators;

use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\Nodes\IdentifierNode;
use Modules\Templating\Compiler\Nodes\OperatorNode;
use Modules\Templating\Compiler\Operator;

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
        if ($left instanceof IdentifierNode) {
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
