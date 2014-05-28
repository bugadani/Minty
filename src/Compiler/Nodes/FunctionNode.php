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

class FunctionNode extends IdentifierNode
{
    private $arguments;

    /**
     * @var Node|null
     */
    private $object;

    public function __construct($functionName, array $arguments = array())
    {
        parent::__construct($functionName);
        $this->setArguments($arguments);
    }

    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
    }

    public function getArguments()
    {
        return $this->arguments;
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

    public function compile(Compiler $compiler)
    {
        $environment = $compiler->getEnvironment();

        $name = $this->getName();
        if ($this->getObject()) {
            $compiler
                ->compileNode($this->getObject())
                ->add('->')
                ->add($name)
                ->compileArgumentList($this->arguments);
        } elseif ($environment->hasFunction($name)) {
            $function = $environment->getFunction($name);
            $environment
                ->getFunctionCompiler($function->getOption('compiler'))
                ->compile($compiler, $function, $this->arguments);
        } else {
            $compiler
                ->add('$this->')
                ->add($name)
                ->compileArgumentList($this->arguments);
        }
    }
}
