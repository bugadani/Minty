<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Nodes;

use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\Exceptions\CompileException;
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
        $this->arguments = $arguments;
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

        if ($this->getObject()) {
            $compiler
                ->compileNode($this->getObject())
                ->add('->')
                ->add($this->getName())
                ->compileArgumentList($this->arguments);
        } else {
            $function = $environment->getFunction($this->getName());

            if ($function->getOption('needs_environment')) {
                $getEnvironmentNode = new FunctionNode('getEnvironment');
                $getEnvironmentNode->setObject(new TempVariableNode('context'));
                array_unshift($this->arguments, $getEnvironmentNode);
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
