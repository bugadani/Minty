<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating;

use ArrayAccess;
use BadMethodCallException;
use InvalidArgumentException;
use OutOfBoundsException;
use Traversable;
use UnexpectedValueException;

abstract class Template
{
    /**
     * @var TemplateLoader
     */
    private $loader;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var array
     */
    private $variables = array();

    private $parentTemplate = false;

    public function __construct(TemplateLoader $loader, Environment $environment)
    {
        $this->loader      = $loader;
        $this->environment = $environment;
    }

    protected function setParentTemplate($parentTemplate)
    {
        $this->parentTemplate = $parentTemplate;
    }

    public function getLoader()
    {
        return $this->loader;
    }

    public function getEnvironment()
    {
        return $this->environment;
    }

    public function clean()
    {
        $this->variables = array();
    }

    public function loadGlobals()
    {
        $this->set($this->environment->getOption('global_variables'));
    }

    public function set($variables)
    {
        if (!is_array($variables)) {
            if (!method_exists($variables, 'toArray')) {
                throw new InvalidArgumentException('Set expects an array as parameter.');
            }
            $variables = $variables->toArray();
        }
        $this->variables = array_merge($this->variables, $variables);
    }

    public function getExtension($name)
    {
        return $this->environment->getExtension($name);
    }

    public function __call($function, $args)
    {
        if ($function === 'empty') {
            return $this->isEmpty(current($args));
        }

        return $this->environment->getFunction($function)->callFunction($args);
    }

    public function filter($data, $for = 'html')
    {
        if (!is_string($data)) {
            return $data;
        }
        switch ($for) {
            case 'html':
                return htmlspecialchars($data);
            case 'json':
                return json_encode($data);
            default:
                throw new BadMethodCallException("Filter is not found for {$for}");
        }
    }

    public function extract($source, $keys)
    {
        if (is_string($keys)) {
            $keys = array($keys);
        }
        foreach ($keys as $key) {
            $this->$key = $this->getProperty($source, $key);
        }
    }

    public function hasProperty($structure, $key)
    {
        if (is_array($structure)) {
            return (isset($structure[$key]));
        }
        if ($structure instanceof ArrayAccess) {
            if (isset($structure[$key])) {
                return true;
            }
        }
        if (is_object($structure)) {
            return isset($structure->$key);
        }
        throw new UnexpectedValueException('Variable is not an array or an object.');
    }

    public function hasMethod($object, $method)
    {
        if (!is_object($object)) {
            throw new UnexpectedValueException('Variable is not an object.');
        }

        return method_exists($object, $method);
    }

    public function getProperty($structure, $key)
    {
        if (is_array($structure) || $structure instanceof ArrayAccess) {
            if (isset($structure[$key])) {
                return $structure[$key];
            }
        }
        if (is_object($structure)) {
            return $structure->$key;
        }
        if (!$this->environment->getOption('strict_mode', true)) {
            return $key;
        }
        throw new UnexpectedValueException('Variable is not an array or an object.');
    }

    public function isEmpty($data)
    {
        return empty($data);
    }

    public function isDivisibleBy($data, $num)
    {
        $div = $data / $num;

        return $div === (int) $div;
    }

    public function isIn($needle, $haystack)
    {
        if (is_string($haystack)) {
            return strpos($haystack, $needle) !== false;
        }
        if ($haystack instanceof Traversable) {
            $haystack = iterator_to_array($haystack);
        }
        if (is_array($haystack)) {
            return in_array($needle, $haystack);
        }
        throw new InvalidArgumentException('The in keyword expects an array, a string or a Traversable instance');
    }

    public function startsWith($data, $str)
    {
        return strpos($data, $str) === 0;
    }

    public function endsWith($data, $str)
    {
        return strpos($data, $str) === strlen($data) - strlen($str);
    }

    public function getParentTemplate()
    {
        return $this->parentTemplate;
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
        throw new OutOfBoundsException("Variable {$key} is not set.");
    }

    public function __isset($key)
    {
        return isset($this->variables[$key]);
    }

    public function getEmbeddedTemplates()
    {
        return array();
    }

    abstract public function render();
}
