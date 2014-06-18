<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <bugadani@gmail.com>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Extensions\Compilers;

use Minty\Compiler\Compiler;
use Minty\Compiler\FunctionCompiler;
use Minty\Compiler\TemplateFunction;

/**
 * @author Dániel Buga <bugadani@gmail.com>
 */
class DefaultCompiler extends FunctionCompiler
{

    public function compile(Compiler $compiler, TemplateFunction $function, array $arguments)
    {
        if (count($arguments) !== 2) {
            throw new \InvalidArgumentException('Default function needs two arguments.');
        }
        $compiler->add('(isset(')
            ->compileNode($arguments[0])
            ->add(') ? ')
            ->compileNode($arguments[0])
            ->add(' : ')
            ->compileNode($arguments[1])
            ->add(')');
    }
}
