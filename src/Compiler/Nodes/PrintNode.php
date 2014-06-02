<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Nodes;

use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\Node;

class PrintNode extends Node
{

    public function compile(Compiler $compiler)
    {
        $compiler
            ->indented('echo ')
            ->compileNode($this->getChild('expression'))
            ->add(';');
    }
}
