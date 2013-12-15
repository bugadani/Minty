<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating;

use ArrayAccess;
use InvalidArgumentException;
use Modules\Templating\Compiler\Environment;
use OutOfBoundsException;
use Traversable;
use UnexpectedValueException;

abstract class Template
{
    /**
     * @var TemplatingOptions
     */
    private $options;

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
    private $variables;
    private $embedded_instances;

    public function __construct(TemplateLoader $loader, Environment $environment)
    {
        $this->options            = $environment->getOptions();
        $this->loader             = $loader;
        $this->environment        = $environment;
        $this->variables          = array();
        $this->embedded_instances = array();
    }

    public function set($variables)
    {
        if (!is_array($variables)) {
            if (method_exists($variables, 'toArray')) {
                $variables = $variables->toArray();
            } else {
                throw new InvalidArgumentException('Set expects an array as parameter.');
            }
        }
        $this->variables = array_merge($this->variables, $variables);
    }

    public function cycle(array &$array)
    {
        $element = each($array);
        if ($element === false) {
            reset($array);
            $element = each($array);
        }
        return $element['value'];
    }

    public function callFilter($filter)
    {
        $args = func_get_args();
        array_shift($args);
        return $this->environment->getFunction($filter)->callFilter($args);
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
                $method = 'filter_' . $for;
                return $this->plugins->$method($data);
        }
    }

    public function getProperty($structure, $key)
    {
        if (is_array($structure) || $structure instanceof ArrayAccess) {
            return $structure[$key];
        }
        if (is_object($structure)) {
            return $structure->$key;
        }
        if (!$this->options->strict_mode) {
            return $key;
        }
        throw new UnexpectedValueException('Variable is not an array or an object.');
    }

    private function instantiateEmbedded($namespace, $template_name)
    {
        $class = $namespace . '\\' . $template_name;
        if (!isset($this->embedded_instances[$class])) {
            $this->embedded_instances[$class] = new $class($this->loader, $this->environment);
        }
        return $this->embedded_instances[$class];
    }

    public function embed($namespace, $template_name, array $args)
    {
        $template = $this->instantiateEmbedded($namespace, $template_name);
        $template->set($args);
        $template->render();
    }

    public function template($template_name, array $args)
    {
        $template = $this->loader->load($template_name);
        $template->set($args);
        $template->render();
    }

    public function listArrayElements($array, $template = null)
    {
        if (is_array($array) || $array instanceof Traversable) {
            if ($template === null) {
                return implode('', $array);
            }
            $object = $this->loader->load($template);
            foreach ($array as $element) {
                $object->set($element);
                echo $object->render();
            }
        } else {
            return $array;
        }
    }

    public function isEmpty($data)
    {
        return empty($data);
    }

    public function isOdd($data)
    {
        return $data % 2 == 1;
    }

    public function isEven($data)
    {
        return $data % 2 == 0;
    }

    public function isDivisibleBy($data, $num)
    {
        $div = $data / $num;
        return $div === (int) $div;
    }

    public function isLike($data, $pattern, $modifiers = 'u')
    {
        $preg_pattern = sprintf('/%s/%s', $pattern, $modifiers);
        return preg_match($preg_pattern, $data);
    }

    public function isSameAs($data, $value)
    {
        return $data === $value;
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
            return in_array($haystack);
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
        return false;
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
        if (!$this->options->strict_mode) {
            return $key;
        }
        throw new OutOfBoundsException(sprintf('Variable %s is not set.', $key));
    }

    public function __isset($key)
    {
        return isset($this->variables[$key]);
    }

    abstract public function render();
}
