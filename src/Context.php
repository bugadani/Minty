<?php

namespace Modules\Templating;

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
        } elseif (!$variables instanceof \Traversable) {
            $variables = iterator_to_array($variables);
        } elseif (method_exists($variables, 'toArray')) {
            $variables = $variables->toArray();
        } else {
            throw new \InvalidArgumentException('Set expects an array as parameter.');
        }

        return $variables;
    }
}
