<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Nodes;

use Minty\Compiler\Compiler;

class VariableNode extends IdentifierNode
{
    public function compile(Compiler $compiler)
    {
        if ($this->getName() === '_self') {
            $compiler->add('$this');
        } else {
            $compiler
                ->add('$context->')
                ->add($this->getName());
        }
    }
}
