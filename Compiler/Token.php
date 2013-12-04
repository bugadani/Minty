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
    const TAG                 = 0;
    const TEXT                = 1;
    const IDENTIFIER          = 2;
    const LITERAL             = 3;
    const STRING              = 4;
    const KEYWORD             = 5;
    const OPERATOR            = 6;
    const BLOCK_START         = 7;
    const BLOCK_END           = 8;
    const EXPRESSION_START    = 9;
    const EXPRESSION_END      = 10;
    const ARGUMENT_LIST_START = 11;
    const ARGUMENT_LIST_END   = 12;
    const TEST                = 13;
    const EOF                 = 14;

    private static $strings = array(
        self::BLOCK_START         => 'BLOCK START',
        self::BLOCK_END           => 'BLOCK END',
        self::EXPRESSION_START    => 'EXPRESSION START',
        self::EXPRESSION_END      => 'EXPRESSION END',
        self::ARGUMENT_LIST_START => 'ARGUMENT_LIST START',
        self::ARGUMENT_LIST_END   => 'ARGUMENT_LIST END',
        self::TEXT                => 'TEXT',
        self::IDENTIFIER          => 'IDENTIFIER',
        self::LITERAL             => 'LITERAL',
        self::KEYWORD             => 'KEYWORD',
        self::OPERATOR            => 'OPERATOR',
        self::TAG                 => 'TAG',
        self::STRING              => 'STRING',
        self::TEST                => 'TEST',
        self::EOF                 => 'EOF'
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
        if (is_callable($value) || $value instanceof Closure) {
            return $value($this->value);
        }
        if (is_array($value)) {
            $valid = false;
            foreach ($value as $v) {
                if ($this->value === $v) {
                    $valid = true;
                    break;
                }
            }
            return $valid;
        } elseif ($this->value != $value) {
            return false;
        }
        return true;
    }

    private function testArray(array $type)
    {
        foreach ($type as $t) {
            if (is_array($t)) {
                if (call_user_func_array(array($this, 'test'), $t)) {
                    return true;
                }
            }
            if ($this->test($t)) {
                return true;
            }
        }
        return false;
    }

    public function test($type, $value = null, $line = 0)
    {
        if (is_array($type)) {
            return $this->testArray($type);
        }
        if ($this->type !== $type) {
            return false;
        }

        if ($this->value !== null && $value !== null) {
            if (!$this->checkValue($value)) {
                return false;
            }
        }

        if ($this->line !== 0 && $line !== 0) {
            if ($this->line != $line) {
                return false;
            }
        }

        return true;
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
