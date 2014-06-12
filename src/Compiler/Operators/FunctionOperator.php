<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Operators;

use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\Nodes\FunctionNode;
use Modules\Templating\Compiler\Nodes\OperatorNode;
use Modules\Templating\Compiler\Operator;

abstract class FunctionOperator extends Operator
{

    public function compile(Compiler $compiler, OperatorNode $node)
    {
        $functionNode = new FunctionNode($this->getFunctionName(), array_reverse(
            $node->getChildren()
        ));
        $compiler->compileNode($functionNode);
    }

    abstract protected function getFunctionName();
}
