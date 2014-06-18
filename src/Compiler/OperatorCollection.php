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
    private $operators = array();

    public function exists(Operator $operator)
    {
        return in_array($operator, $this->operators, true);
    }

    /**
     * @param Operator[] $operators
     */
    public function addOperators(array $operators)
    {
        foreach ($operators as $operator) {
            $this->addOperator($operator);
        }
    }

    /**
     * @param Operator $operator
     */
    public function addOperator(Operator $operator)
    {
        $symbol = $operator->operators();
        if (is_array($symbol)) {
            foreach ($symbol as $opSymbol) {
                $this->operators[$opSymbol] = $operator;
            }
        } else {
            $this->operators[$symbol] = $operator;
        }
    }

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
