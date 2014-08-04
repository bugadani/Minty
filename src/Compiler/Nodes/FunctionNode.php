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

class FunctionNode extends IdentifierNode
{
    private $arguments;
    private $argumentCount;

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

        $this->argumentCount = 0;
        foreach ($arguments as $argument) {
            $this->addChild($argument, 'argument_' . $this->argumentCount++);
        }
    }

    public function addArgument($argument)
    {
        $this->arguments[] = $argument;
        $this->addChild($argument, 'argument_' . $this->argumentCount++);
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
        if ($this->getObject()) {
            $compiler
                ->compileNode($this->getObject())
                ->add('->' . $this->getData('name'))
                ->compileArgumentList($this->arguments);
        } else {
            $environment = $compiler->getEnvironment();
            $function    = $environment->getFunction($this->getData('name'));

            if ($function->getOption('needs_environment')) {
                array_unshift($this->arguments, new TempVariableNode('environment'));
            }
            if ($function->getOption('needs_context')) {
                array_unshift($this->arguments, new TempVariableNode('context'));
            }

            $environment
                ->getFunctionCompiler($function->getOption('compiler'))
                ->compile($compiler, $function, $this->arguments);
        }
    }
}
