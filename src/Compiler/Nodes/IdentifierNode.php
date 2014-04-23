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

class IdentifierNode extends Node
{
    private $name;

    /**
     * @var Node|null
     */
    private $object;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function setObject(Node $object)
    {
        $this->object = $object;
    }

    /**
     * @return Node|null
     */
    public function getObject()
    {
        return $this->object;
    }

    public function getName()
    {
        return $this->name;
    }

    public function compile(Compiler $compiler)
    {
        if ($this->object) {
            $compiler->compileNode($this->getObject());
        } else {
            $compiler->add('$this');
        }
        $compiler
            ->add('->')
            ->add($this->name);
    }
}
