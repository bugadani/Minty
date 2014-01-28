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
use Modules\Templating\Compiler\Operator;

class BinaryOperatorNode extends Node
{
    /**
     * @var Operator
     */
    private $operator;

    /**
     * @var Node
     */
    public $operand_left;

    /**
     * @var Node
     */
    public $operand_right;

    /**
     * @param Operator $operator
     * @param Node $left
     * @param Node $right
     */
    public function __construct(Operator $operator, Node $left, Node $right)
    {
        $this->operator      = $operator;
        $this->operand_left  = $left;
        $this->operand_right = $right;
    }

    public function compile(Compiler $compiler)
    {
        $this->operator->compile($compiler, $this);
    }
}
