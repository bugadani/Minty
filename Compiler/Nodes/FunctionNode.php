<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Nodes;

use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\Functions\CallbackFunction;
use Modules\Templating\Compiler\Functions\MethodFunction;
use Modules\Templating\Compiler\Functions\SimpleFunction;
use Modules\Templating\Compiler\Node;

class FunctionNode extends Node
{
    private $function_name;
    private $arguments;
    private $object;

    function __construct($function_name)
    {
        $this->function_name = $function_name;
        $this->arguments     = array();
        $this->object        = null;
    }

    public function setObject(Node $object)
    {
        $this->object = $object;
    }

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

    public function getArguments()
    {
        return $this->arguments;
    }

    public function compile(Compiler $compiler)
    {
        if ($this->object !== null) {
            $this->object->compile($compiler);
            $compiler->add('->');
            $compiler->add($this->function_name->getName());
        } else {
            $func_name   = $this->function_name->getName();
            $environment = $compiler->getEnvironment();
            if ($environment->hasFunction($func_name)) {
                $function = $environment->getFunction($func_name);

                if ($function instanceof SimpleFunction) {
                    $compiler->add($function->getFunction());
                } elseif ($function instanceof MethodFunction) {
                    $compiler->add('$this->getExtension(');
                    $compiler->add($compiler->string($function->getExtensionName()));
                    $compiler->add(')->');
                    $compiler->add($function->getMethod());
                } elseif ($function instanceof CallbackFunction) {
                    $compiler->add('$this->');
                    $compiler->add($function->getFunctionName());
                }
            } else {
                $compiler->add('$this->');
                $compiler->add($func_name);
            }
        }
        $compiler->add('(');
        $first = true;
        foreach ($this->arguments as $argument) {
            if ($first) {
                $first = false;
            } else {
                $compiler->add(', ');
            }
            $compiler->add($compiler->compileData($argument));
        }
        $compiler->add(')');
    }
}
