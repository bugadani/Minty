<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler;

class OperatorCollection
{
    /**
     * @var Operator[]
     */
    private $operators = [];

    public function exists(Operator $operator)
    {
        return in_array($operator, $this->operators, true);
    }

    /**
     * @param Operator[] $operators
     */
    public function addOperators(array $operators)
    {
        array_map([$this, 'addOperator'], $operators);
    }

    /**
     * @param Operator $operator
     */
    public function addOperator(Operator $operator)
    {
        $symbol = $operator->operators();
        foreach ((array)$symbol as $opSymbol) {
            $this->operators[$opSymbol] = $operator;
        }
    }

    /**
     * Returns whether $operator is an operator symbol.
     *
     * @param $operator
     *
     * @return bool
     */
    public function isOperator($operator)
    {
        return isset($this->operators[$operator]);
    }

    public function getOperator($sign)
    {
        return $this->operators[$sign];
    }

    public function getSymbols()
    {
        return array_keys($this->operators);
    }
}
