<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Nodes;

use Minty\Compiler\Compiler;

class TempVariableNode extends IdentifierNode
{
    public function compile(Compiler $compiler)
    {
        $compiler->add('$' . $this->getName());
    }
}
