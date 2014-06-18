<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Operators\ExistenceOperators;

use Minty\Compiler\Compiler;
use Minty\Compiler\Nodes\OperatorNode;

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
