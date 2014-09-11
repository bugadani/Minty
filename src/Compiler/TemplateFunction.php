<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Minty\Compiler;

use Minty\Extension;

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
     * @var Extension
     */
    private $extension;

    /**
     * @param string $name
     * @param        $callback
     * @param array  $options
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($name, $callback = null, array $options = [])
    {
        $this->name = $name;
        if (!isset($options['compiler'])) {
            if ($callback === null) {
                $callback = $name;
            }
            if (!is_callable($callback)) {
                throw new \InvalidArgumentException("\$callback for function {$name} must be a callable value");
            }
            $this->callback = $callback;
        }

        $defaults = [
            'is_safe'           => false,
            'compiler'          => __NAMESPACE__ . '\\FunctionCompiler',
            'needs_context'     => false,
            'needs_environment' => false
        ];

        $this->options = $options + $defaults;
    }

    public function setExtension(Extension $extension)
    {
        $this->extension = $extension;
    }

    public function getExtension()
    {
        return $this->extension;
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
     * @throws \BadMethodCallException
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
