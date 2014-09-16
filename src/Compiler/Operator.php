<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler;

use Minty\Compiler\Nodes\OperatorNode;

abstract class Operator
{
    const LEFT = 0;
    const RIGHT = 1;
    const NONE = 2;

    private $precedence;
    private $associativity;

    public function __construct($precedence, $associativity = self::LEFT)
    {
        $this->precedence    = $precedence;
        $this->associativity = $associativity;
    }

    public function getPrecedence()
    {
        return $this->precedence;
    }

    public function isAssociativity($associativity)
    {
        return $this->associativity === $associativity;
    }

    public function createNode(Node $left = null, Node $right = null, Node $middle = null)
    {
        $node = new OperatorNode($this);
        if($left) {
            $node->addChild($left, OperatorNode::OPERAND_LEFT);
        }
        if($middle) {
            $node->addChild($middle, OperatorNode::OPERAND_MIDDLE);
        }
        if($right) {
            $node->addChild($right, OperatorNode::OPERAND_RIGHT);
        }

        return $node;
    }

    abstract public function operators();

    abstract public function compile(Compiler $compiler, OperatorNode $node);
}
