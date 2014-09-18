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

class PrintNode extends Node
{
    public function __construct($expression)
    {
        if (!$expression instanceof Node) {
            $expression = new DataNode($expression);
        }
        $this->addChild($expression, 'expression');
    }

    public function compile(Compiler $compiler)
    {
        $compiler
            ->indented('echo ')
            ->compileNode($this->getChild('expression'))
            ->add(';');
    }
}
