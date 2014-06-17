<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Modules\Templating\Compiler;

class TemplateFunction
{
    /**
     * @var array
     */
    private $options;

    /**
     * @var string
     */
    private $name;

    /**
     * @var callable
     */
    private $callback;

    /**
     * @param string $name
     * @param        $callback
     * @param array  $options
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($name, $callback = null, array $options = array())
    {
        $this->name = $name;
        if ($callback === null) {
            $callback = $name;
        }
        if (!is_callable($callback) && !isset($options['compiler'])) {
            throw new \InvalidArgumentException("\$callback for function {$name} must be a callable value");
        }
        $this->callback = $callback;
        $defaults       = array(
            'is_safe'           => false,
            'compiler'          => __NAMESPACE__ . '\\FunctionCompiler',
            'needs_context'     => false,
            'needs_environment' => false
        );
        $this->options  = array_merge($defaults, $options);
    }

    public function getOption($key)
    {
        if (!isset($this->options[$key])) {
            throw new \OutOfBoundsException("Option {$key} is not set.");
        }

        return $this->options[$key];
    }

    public function getFunctionName()
    {
        return $this->name;
    }

    /**
     * @return callable
     */
    public function getCallback()
    {
        return $this->callback;
    }

    public function call()
    {
        return call_user_func_array($this->callback, func_get_args());
    }
}
