<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty;

class Context
{
    private $strictMode;
    private $variables;

    public function __construct($strictMode, array $variables = [])
    {
        $this->variables  = $variables;
        $this->strictMode = (bool) $strictMode;
    }

    public function __set($key, $value)
    {
        $this->variables[$key] = $value;
    }

    public function __unset($key)
    {
        unset($this->variables[$key]);
    }

    public function &__get($key)
    {
        if (isset($this->variables[$key])) {
            return $this->variables[$key];
        }
        if (!$this->strictMode) {
            return $key;
        }
        throw new \OutOfBoundsException("Variable {$key} is not set.");
    }

    public function __isset($key)
    {
        return isset($this->variables[$key]);
    }

    public function toArray()
    {
        return $this->variables;
    }

    public function getProperty($structure, $key)
    {
        if (is_array($structure) || $structure instanceof \ArrayAccess) {
            if (isset($structure[$key])) {
                return $structure[$key];
            }
        }
        if (is_object($structure)) {
            if (isset($structure->$key)) {
                return $structure->$key;
            }
            $methodName = 'get' . ucfirst($key);
            if (method_exists($structure, $methodName)) {
                return $structure->$methodName();
            }
        }
        if (!$this->strictMode) {
            return $key;
        }
        throw new \UnexpectedValueException("Property {$key} is not set.");
    }

    public function hasProperty($structure, $key)
    {
        if (is_array($structure)) {
            return isset($structure[$key]);
        }
        if ($structure instanceof \ArrayAccess) {
            if (isset($structure[$key])) {
                return true;
            }
        }
        if (is_object($structure)) {
            if (isset($structure->$key)) {
                return true;
            }
            $methodName = 'has' . ucfirst($key);
            if (method_exists($structure, $methodName)) {
                return $structure->$methodName();
            }

            return false;
        }
        throw new \UnexpectedValueException('Variable is not an array or an object.');
    }
}
