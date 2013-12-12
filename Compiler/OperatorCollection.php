<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Modules\Templating\Compiler;

/**
 * Description of OperatorCollection
 *
 * @author Dániel
 */
class OperatorCollection
{
    private $operators;

    public function __construct()
    {
        $this->operators = array();
    }

    public function exists(Operator $operator)
    {
        return in_array($operator, $this->operators, true);
    }

    public function addOperator(Operator $operator)
    {
        $symbol = $operator->operators();
        if (is_array($symbol)) {
            foreach ($symbol as $op_symbol) {
                $this->operators[$op_symbol] = $operator;
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
