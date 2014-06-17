<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <bugadani@gmail.com>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Extensions\Compilers;

use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\FunctionCompiler;
use Modules\Templating\Compiler\TemplateFunction;

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
