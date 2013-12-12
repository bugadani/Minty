<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler;

use Closure;

class Token
{
    const EXPRESSION_START = 0;
    const EXPRESSION_END   = 1;
    const BLOCK_START      = 2;
    const BLOCK_END        = 3;
    const LITERAL          = 4;
    const STRING           = 5;
    const IDENTIFIER       = 6;
    const OPERATOR         = 7;
    const PUNCTUATION      = 8;
    const TEXT             = 9;
    const TAG              = 10;
    const EOF              = 11;

    private static $strings = array(
        self::EXPRESSION_START => 'EXPRESSION START',
        self::EXPRESSION_END   => 'EXPRESSION END',
        self::BLOCK_START      => 'BLOCK START',
        self::BLOCK_END        => 'BLOCK END',
        self::LITERAL          => 'LITERAL',
        self::STRING           => 'STRING',
        self::IDENTIFIER       => 'IDENTIFIER',
        self::OPERATOR         => 'OPERATOR',
        self::PUNCTUATION      => 'PUNCTUATION',
        self::TEXT             => 'TEXT',
        self::TAG              => 'TAG',
        self::EOF              => 'EOF',
    );
    private $type;
    private $value;
    private $line;

    public function __construct($type, $value = null, $lineno = 0)
    {
        $this->type  = $type;
        $this->value = $value;
        $this->line  = $lineno;
    }

    private function checkValue($value)
    {
        if ($this->value === null) {
            return true;
        }
        if ($value === null) {
            return true;
        }
        if (is_callable($value) || $value instanceof Closure) {
            return $value($this->value);
        }
        return $this->value === $value;
    }

    public function test($type, $value = null, $line = 0)
    {
        if ($this->type !== $type) {
            return false;
        }

        if (!$this->checkValue($value)) {
            return false;
        }

        if ($this->line !== 0 && $line !== 0) {
            if ($this->line != $line) {
                return false;
            }
        }

        return true;
    }

    public function isDataType()
    {
        if ($this->test(Token::LITERAL)) {
            return true;
        }
        if ($this->test(Token::STRING)) {
            return true;
        }
        if ($this->test(Token::IDENTIFIER)) {
            return true;
        }
        return false;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getLine()
    {
        return $this->line;
    }

    public function getTypeString()
    {
        if (isset(self::$strings[$this->type])) {
            return self::$strings[$this->type];
        }
        return sprintf('UNKNOWN %s', $this->type);
    }
}
