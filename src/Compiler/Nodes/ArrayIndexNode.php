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

class ArrayIndexNode extends Node
{

    public function __construct(Node $identifier, Node $key)
    {
        $this->addChild($identifier, 'identifier');
        $this->addChild($key, 'key');
    }

    public function compile(Compiler $compiler)
    {
        $compiler->compileNode($this->getChild('identifier'))
            ->add('[')
            ->compileNode($this->getChild('key'))
            ->add(']');
    }
}
