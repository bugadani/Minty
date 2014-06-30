<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Operators;

use Minty\Compiler\Compiler;
use Minty\Compiler\Exceptions\ParseException;
use Minty\Compiler\Nodes\FunctionNode;
use Minty\Compiler\Nodes\IdentifierNode;
use Minty\Compiler\Nodes\OperatorNode;
use Minty\Compiler\Nodes\VariableNode;
use Minty\Compiler\Operator;

class FilterOperator extends Operator
{

    public function operators()
    {
        return '|';
    }

    public function compile(Compiler $compiler, OperatorNode $node)
    {
        $data   = $node->getChild(OperatorNode::OPERAND_LEFT);
        $filter = $node->getChild(OperatorNode::OPERAND_RIGHT);
        if ($filter instanceof FunctionNode) {
            $arguments = $filter->getArguments();
            array_unshift($arguments, $data);
            $filter->setArguments($arguments);
        } elseif ($filter instanceof IdentifierNode && !$filter instanceof VariableNode) {
            $filter = new FunctionNode($filter->getData('name'), array($data));
        } else {
            throw new ParseException('Invalid filter node.');
        }
        $filter->compile($compiler);
    }
}
