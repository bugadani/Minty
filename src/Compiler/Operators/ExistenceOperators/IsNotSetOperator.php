<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Operators\ExistenceOperators;

use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\Nodes\ArrayIndexNode;
use Modules\Templating\Compiler\Nodes\FunctionNode;
use Modules\Templating\Compiler\Nodes\IdentifierNode;
use Modules\Templating\Compiler\Nodes\OperatorNode;
use Modules\Templating\Compiler\Operator;
use Modules\Templating\Compiler\Operators\PropertyAccessOperator;

class IsNotSetOperator extends IsSetOperator
{

    public function operators()
    {
        return 'is not set';
    }

    public function compile(Compiler $compiler, OperatorNode $node)
    {
        $compiler->add('!');
        parent::compile($compiler, $node);
    }
}
