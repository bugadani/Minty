<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Modules\Templating\Compiler;

use OutOfBoundsException;

abstract class TemplateFunction
{
    /**
     * @var array
     */
    private $options;
    private $name;
    private $extension;

    /**
     * @param string $name
     * @param array  $options
     */
    public function __construct($name, array $options = array())
    {
        $this->name    = $name;
        $defaults      = array(
            'is_safe'  => false,
            'compiler' => __NAMESPACE__ . '\\FunctionCompiler'
        );
        $this->options = array_merge($defaults, $options);
    }

    public function getOption($key)
    {
        if (!isset($this->options[$key])) {
            throw new OutOfBoundsException("Option {$key} is not set.");
        }

        return $this->options[$key];
    }

    public function setExtensionName($extension)
    {
        $this->extension = $extension;
    }

    public function getExtensionName()
    {
        return $this->extension;
    }

    /**
     * @return bool
     */
    public function isSafe()
    {
        return $this->options['is_safe'];
    }

    public function getFunctionName()
    {
        return $this->name;
    }
}
