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

class FunctionNode extends Node
{
    /**
     * @var string
     */
    private $function_name;

    private $arguments;

    /**
     * @var Node|null
     */
    private $object;

    public function __construct($function_name, array $arguments = array())
    {
        $this->function_name = $function_name;
        $this->arguments     = $arguments;
        $this->object        = null;
    }

    public function setObject(Node $object)
    {
        $this->object = $object;
    }

    /**
     * @return string
     */
    public function getFunctionName()
    {
        return $this->function_name;
    }

    public function setArguments(array $arguments)
    {
        $this->arguments = $arguments;
    }

    public function getArguments()
    {
        return $this->arguments;
    }

    public function compile(Compiler $compiler)
    {
        $environment = $compiler->getEnvironment();
        if ($this->object !== null) {
            $this->object->compile($compiler);
            $compiler
                ->add('->')
                ->add($this->function_name);
            $compiler->compileArgumentList($this->arguments);
        } elseif ($environment->hasFunction($this->function_name)) {
            $function = $environment->getFunction($this->function_name);
            $environment
                ->getFunctionCompiler($function->getOption('compiler'))
                ->compile($compiler, $function, $this->arguments);
        } else {
            $compiler
                ->add('$this->')
                ->add($this->function_name);
            $compiler->compileArgumentList($this->arguments);
        }
    }
}
