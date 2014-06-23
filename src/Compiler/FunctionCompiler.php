<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Minty\Compiler;

class FunctionCompiler
{

    public function compile(Compiler $compiler, TemplateFunction $function, array $arguments)
    {
        $callback = $function->getCallback();

        //simple functions are compiled directly
        if (is_string($callback) && strpos($callback, ':') === false) {
            $compiler->add($callback);
        } else {
            $compiler
                ->add('$environment->getFunction(')
                ->compileString($function->getFunctionName())
                ->add(')->call');
        }
        $compiler->compileArgumentList($arguments);
    }
}
