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

class NotMatchesOperator extends MatchesOperator
{

    public function operators()
    {
        return array('does not match', 'is not like', 'not matches');
    }

    public function compile(Compiler $compiler, OperatorNode $node)
    {
        $compiler->add('!');
        parent::compile($compiler, $node);
    }
}
