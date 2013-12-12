<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler;

use Modules\Templating\Compiler\Nodes\OperatorNode;

abstract class Operator
{
    const LEFT  = 0;
    const RIGHT = 1;
    const NONE  = 2;

    private $precedence;
    private $associativity;
    protected $operators;

    public function __construct($precedence, $associativity = self::LEFT)
    {
        $this->precedence    = $precedence;
        $this->associativity = $associativity;

        $operators = $this->operators();
        if (!is_array($operators)) {
            $operators = array($operators);
        }
        $this->operators = $operators;
    }

    abstract public function operators();

    public function getPrecedence()
    {
        return $this->precedence;
    }

    public function isAssociativity($associativity)
    {
        return $this->associativity === $associativity;
    }

    abstract public function compile(Compiler $compiler, OperatorNode $node);
}
