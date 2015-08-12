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
    private $variables;

    public function __construct(array $variables = [])
    {
        $this->variables = $variables;
    }

    public function __set($key, $value)
    {
        $this->variables[ $key ] = $value;
    }

    public function __unset($key)
    {
        unset($this->variables[ $key ]);
    }

    public function &__get($key)
    {
        if (isset($this->variables[ $key ])) {
            return $this->variables[ $key ];
        }
        throw new \OutOfBoundsException("Variable {$key} is not set.");
    }

    public function __isset($key)
    {
        return isset($this->variables[ $key ]);
    }

    public function toArray()
    {
        return $this->variables;
    }

    private function &findProperty($structure, array $keys)
    {
        foreach ($keys as $key) {
            if (is_array($structure) || $structure instanceof \ArrayAccess) {
                if (isset($structure[ $key ])) {
                    $structure = $structure[ $key ];
                    continue;
                }
            }
            if (is_object($structure)) {
                if (isset($structure->$key)) {
                    $structure = $structure->$key;
                    continue;
                }
                $methodName = 'get' . ucfirst($key);
                if (method_exists($structure, $methodName)) {
                    $structure = $structure->$methodName();
                    continue;
                }
            }
            throw new \OutOfBoundsException("Property {$key} is not set.");
        }

        return $structure;
    }

    public function getProperty($structure, array $keys)
    {
        return $this->findProperty($structure, $keys);
    }

    public function hasProperty($structure, array $keys)
    {
        try {
            $this->findProperty($structure, $keys);
            return true;
        } catch (\OutOfBoundsException $e) {
            return false;
        }
    }

    public function setProperty($structure, array $keys, $value)
    {
        $lastKey   = array_pop($keys);
        $structure = $this->findProperty($structure, $keys);
        if (is_array($structure) || $structure instanceof \ArrayAccess) {
            $structure[ $lastKey ] = $value;
        } elseif (is_object($structure)) {
            $methodName = 'set' . ucfirst($lastKey);
            if (method_exists($structure, $methodName)) {
                $structure->$methodName($value);
            } else {
                $structure->$lastKey = $value;
            }
        } else {
            throw new \UnexpectedValueException("Property {$lastKey} can not be set.");
        }
    }
}
