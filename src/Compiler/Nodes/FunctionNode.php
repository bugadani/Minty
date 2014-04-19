<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Nodes;

use Modules\Templating\Compiler\Compiler;

class FunctionNode extends IdentifierNode
{
    private $arguments;

    public function __construct($function_name, array $arguments = array())
    {
        parent::__construct($function_name);
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

    public function compile(Compiler $compiler)
    {
        $environment = $compiler->getEnvironment();

        if (!$this->getObject()) {
            $name = $this->getName();
            if ($environment->hasFunction($name)) {
                $function = $environment->getFunction($name);
                $environment
                    ->getFunctionCompiler($function->getOption('compiler'))
                    ->compile($compiler, $function, $this->arguments);

                return;
            }
        }
        parent::compile($compiler);
        $compiler->compileArgumentList($this->arguments);
    }
}
