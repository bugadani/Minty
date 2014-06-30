<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Operators;

use Minty\Compiler\Compiler;
use Minty\Compiler\Nodes\FunctionNode;
use Minty\Compiler\Nodes\OperatorNode;
use Minty\Compiler\Operator;

abstract class FunctionOperator extends Operator
{

    public function compile(Compiler $compiler, OperatorNode $node)
    {
        $compiler->compileNode(
            new FunctionNode(
                $this->getFunctionName(),
                array_reverse($node->getChildren())
            )
        );
    }

    abstract protected function getFunctionName();
}
