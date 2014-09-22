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
        $this->strictMode = (bool)$strictMode;
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
        if (!$this->strictMode) {
            return $key;
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

    public function getProperty($structure, array $keys)
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
            if (!$this->strictMode) {
                return $key;
            }
            throw new \OutOfBoundsException("Property {$key} is not set.");
        }

        return $structure;
    }

    public function hasProperty($structure, array $keys)
    {
        foreach ($keys as $key) {
            if (is_array($structure)) {
                if (!isset($structure[ $key ])) {
                    return false;
                }
                $structure = $structure[ $key ];
            } elseif (is_object($structure)) {
                if ($structure instanceof \ArrayAccess && isset($structure[ $key ])) {
                    $structure = $structure[ $key ];
                    continue;
                }
                if (isset($structure->$key)) {
                    $structure = $structure->$key;
                } else {
                    $methodName = ucfirst($key);
                    if (
                        !method_exists($structure, 'has' . $methodName)
                        || !$structure->{'has' . $methodName}
                    ) {
                        return false;
                    }
                    $structure = $structure->{'get' . $methodName}();
                }
            } else {
                return false;
            }
        }

        return true;
    }

    public function setProperty($structure, array $keys, $value)
    {
        $lastKey = array_pop($keys);
        foreach ($keys as $key) {
            if (is_array($structure) || $structure instanceof \ArrayAccess) {
                $structure =& $structure[ $key ];
            } elseif (is_object($structure)) {
                $methodName = 'get' . ucfirst($key);
                if (method_exists($structure, $methodName)) {
                    $structure =& $structure->$methodName();
                } else {
                    $structure =& $structure->$key;
                }
            } else {
                throw new \UnexpectedValueException("Property {$key} can not be set.");
            }
        }
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
