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
    /**
     * @var Environment
     */
    private $environment;
    private $variables;

    public function __construct(Environment $environment, $variables = array())
    {
        $this->environment = $environment;
        $this->variables   = $this->ensureArray($variables);
    }

    public function getEnvironment()
    {
        return $this->environment;
    }

    public function clean()
    {
        $this->variables = array();
    }

    public function set($variables)
    {
        $this->variables = $this->ensureArray($variables);
    }

    public function add($variables)
    {
        $variables       = $this->ensureArray($variables);
        $this->variables = array_merge($this->variables, $variables);
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
        if (!$this->environment->getOption('strict_mode', true)) {
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
        if (!$this->environment->getOption('strict_mode', true)) {
            return $key;
        }
        throw new \UnexpectedValueException('Variable is not an array or an object.');
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
            if(isset($structure->$key)) {
                return true;
            }
            $methodName = 'has' . ucfirst($key);
            if(method_exists($structure, $methodName)) {
                return $structure->$methodName();
            }
        }
        throw new \UnexpectedValueException('Variable is not an array or an object.');
    }

    /**
     * @param $variables
     *
     * @return mixed
     * @throws \InvalidArgumentException
     */
    private function ensureArray($variables)
    {
        if (is_array($variables)) {
            //do nothing
        } elseif ($variables === null) {
            $variables = array();
        } elseif (method_exists($variables, 'toArray')) {
            $variables = $variables->toArray();
        } elseif ($variables instanceof \Traversable) {
            $variables = iterator_to_array($variables);
        } else {
            throw new \InvalidArgumentException('Set expects an array as parameter.');
        }

        return $variables;
    }
}
