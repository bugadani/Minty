<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Nodes;

use Minty\Compiler\Compiler;
use Minty\Compiler\Node;

class ExpressionNode extends Node
{

    public function __construct(Node $expression)
    {
        $this->addChild($expression, 'expression');
    }

    public function compile(Compiler $compiler)
    {
        $compiler
            ->indented('')
            ->compileNode($this->getChild('expression'))
            ->add(';');
    }
}
