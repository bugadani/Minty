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
        $data   = $node->getOperand(OperatorNode::OPERAND_LEFT);
        $filter = $node->getOperand(OperatorNode::OPERAND_RIGHT);
        if ($filter instanceof FunctionNode) {
            $arguments = $filter->getArguments();
            array_unshift($arguments, $data);
            $filter->setArguments($arguments);
        } elseif ($filter instanceof IdentifierNode) {
            $filter = new FunctionNode($filter->getName(), array($data));
        } else {
            throw new ParseException('Invalid filter node.');
        }
        $filter->compile($compiler);
    }
}
