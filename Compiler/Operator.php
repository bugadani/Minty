<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler;

abstract class Operator
{
    protected $operators;

    public function __construct()
    {
        $operators = $this->operators();
        if (!is_array($operators)) {
            $operators = array($operators);
        }
        $this->operators = $operators;
    }

    abstract public function operators();

    public function parseOperator(Parser $parser, $operator)
    {
        $parser->pushToken(Token::OPERATOR, $operator);
        return true;
    }

    public function parse(Parser $parser, $operator)
    {
        if (!in_array($operator, $this->operators)) {
            return false;
        }
        return $this->parseOperator($parser, $operator);
    }
}
