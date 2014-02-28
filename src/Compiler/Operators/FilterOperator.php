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
        $data          = $node->getOperand(OperatorNode::OPERAND_LEFT);
        $function_node = $node->getOperand(OperatorNode::OPERAND_RIGHT);
        if ($function_node instanceof FunctionNode) {
            $arguments = $function_node->getArguments();
            array_unshift($arguments, $data);
            $function_node->setArguments($arguments);
        } elseif ($function_node instanceof IdentifierNode) {
            $function_node = new FunctionNode($function_node);
            $function_node->addArgument($data);
        } else {
            throw new ParseException('Invalid filter node.');
        }
        $function_node->compile($compiler);
    }
}
