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
     * @var IdentifierNode
     */
    private $function_name;

    private $arguments;

    /**
     * @var Node|null
     */
    private $object;

    public function __construct(IdentifierNode $function_name)
    {
        $this->function_name = $function_name;
        $this->arguments     = array();
        $this->object        = null;
    }

    public function setObject(Node $object)
    {
        $this->object = $object;
    }

    /**
     * @return IdentifierNode
     */
    public function getFunctionName()
    {
        return $this->function_name;
    }

    public function setFunctionName($function_name)
    {
        $this->function_name = $function_name;
    }

    public function addArgument($argument, $prepend = false)
    {
        if ($prepend) {
            array_unshift($this->arguments, $argument);
        } else {
            $this->arguments[] = $argument;
        }
    }

    public function addArguments(array $arguments)
    {
        $this->arguments = array_merge($arguments, $this->arguments);
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
        $func_name   = $this->function_name->getName();
        $environment = $compiler->getEnvironment();
        if ($this->object !== null) {
            $this->object->compile($compiler);
            $compiler
                ->add('->')
                ->add($func_name);
            $compiler->compileArgumentList($this->arguments);
        } elseif ($environment->hasFunction($func_name)) {
            $function = $environment->getFunction($func_name);
            $environment
                ->getFunctionCompiler($function->getOption('compiler'))
                ->compile($compiler, $function, $this->arguments);
        } else {
            $compiler
                ->add('$this->')
                ->add($func_name);
            $compiler->compileArgumentList($this->arguments);
        }
    }
}
