<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Modules\Templating\Compiler;

use Modules\Templating\Compiler\Functions\CallbackFunction;
use Modules\Templating\Compiler\Functions\MethodFunction;
use Modules\Templating\Compiler\Functions\SimpleFunction;

class FunctionCompiler
{

    public function compile(Compiler $compiler, TemplateFunction $function, array $arguments)
    {
        if ($function instanceof SimpleFunction) {
            $compiler->add($function->getFunction());
        } elseif ($function instanceof MethodFunction) {
            $compiler
                    ->add('$this->getExtension(')
                    ->add($compiler->string($function->getExtensionName()))
                    ->add(')->')
                    ->add($function->getMethod());
        } elseif ($function instanceof CallbackFunction) {
            $compiler
                    ->add('$this->')
                    ->add($function->getFunctionName());
        }
        $compiler->compileArgumentList($arguments);
    }
}
