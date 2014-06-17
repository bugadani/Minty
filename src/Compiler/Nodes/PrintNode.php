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

class PrintNode extends Node
{

    public function compile(Compiler $compiler)
    {
        $expression = $this->getChild('expression');
        if (!$this->getData('is_safe')) {
            $arguments = array($expression);

            if($this->hasData('filter_for')) {
                $arguments[] = $this->getData('filter_for');
            }

            $function = new FunctionNode('filter', $arguments);
            $expression->setParent($function);
            $expression = $function;
        }
        $compiler
            ->indented('echo ')
            ->compileNode($expression)
            ->add(';');
    }
}
