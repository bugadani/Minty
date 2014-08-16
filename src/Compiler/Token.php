<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler;

class Token
{
    const TAG_START   = 0;
    const TAG_END     = 1;
    const LITERAL     = 2;
    const STRING      = 3;
    const IDENTIFIER  = 4;
    const OPERATOR    = 5;
    const PUNCTUATION = 6;
    const TEXT        = 7;
    const VARIABLE    = 8;
    const EOF         = 9;

    private static $strings = [
        self::TAG_START   => 'TAG START',
        self::TAG_END     => 'TAG END',
        self::LITERAL     => 'LITERAL',
        self::STRING      => 'STRING',
        self::IDENTIFIER  => 'IDENTIFIER',
        self::OPERATOR    => 'OPERATOR',
        self::PUNCTUATION => 'PUNCTUATION',
        self::TEXT        => 'TEXT',
        self::VARIABLE    => 'VARIABLE',
        self::EOF         => 'EOF',
    ];
    private $type;
    private $value;
    private $line;

    public function __construct($type, $value = null, $line = 0)
    {
        $this->type  = $type;
        $this->value = $value;
        $this->line  = $line;
    }

    public function test($type, $value = null)
    {
        if ($this->type !== $type) {
            return false;
        }
        if ($value === null || $this->value === $value) {
            return true;
        }
        if (is_array($value) && in_array($this->value, $value, true)) {
            return true;
        }
        if (is_callable($value)) {
            return $value($this->value);
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

        return "UNKNOWN {$this->type}";
    }
}
