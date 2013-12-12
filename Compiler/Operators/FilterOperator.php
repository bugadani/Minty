<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Operators;

use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\Exceptions\ParseException;
use Modules\Templating\Compiler\Functions\CallbackFunction;
use Modules\Templating\Compiler\Functions\MethodFunction;
use Modules\Templating\Compiler\Functions\SimpleFunction;
use Modules\Templating\Compiler\Nodes\FunctionNode;
use Modules\Templating\Compiler\Nodes\IdentifierNode;
use Modules\Templating\Compiler\Nodes\OperatorNode;
use Modules\Templating\Compiler\Operator;

class FilterOperator extends Operator
{

    public function operators()
    {
        return '|';
    }

    public function compile(Compiler $compiler, OperatorNode $node)
    {
        $environment   = $compiler->getEnvironment();
        $data          = $node->getOperand(OperatorNode::OPERAND_LEFT);
        $function_node = $node->getOperand(OperatorNode::OPERAND_RIGHT);
        if ($function_node instanceof FunctionNode) {
            $function  = $environment->getFunction($function_node->getFunctionName()->getName());
            $arguments = $function_node->getArguments();
        } elseif ($function_node instanceof IdentifierNode) {
            $function  = $environment->getFunction($function_node->getName());
            $arguments = array();
        } else {
            throw new ParseException('Invalid filter node.');
        }
        array_unshift($arguments, $data);

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

        $compiler->add('(');
        $first = true;
        foreach ($arguments as $argument) {
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
