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

class PrintNode extends Node
{

    public function compile(Compiler $compiler)
    {
        $expression = $this->getChild('expression');
        if (!$this->getData('is_safe')) {
            $arguments = array($expression);

            if ($this->hasChild('filter_for')) {
                $arguments[] = $this->getChild('filter_for');
            } elseif ($this->hasData('filter_for')) {
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
