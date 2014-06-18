<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Operators\TestOperators;

use Minty\Compiler\Compiler;
use Minty\Compiler\Nodes\OperatorNode;

class NotDivisibleByOperator extends DivisibleByOperator
{

    public function operators()
    {
        return array('not divisible by', 'is not divisible by');
    }

    public function compile(Compiler $compiler, OperatorNode $node)
    {
        $compiler->add('!');
        parent::compile($compiler, $node);
    }
}
