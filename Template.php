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
     * @var Plugins
     */
    private $plugins;

    /**
     * @var array
     */
    private $variables;

    public function __construct(TemplatingOptions $options, TemplateLoader $loader, Plugins $plugins)
    {
        $this->options   = $options;
        $this->plugins   = $plugins;
        $this->loader    = $loader;
        $this->variables = array();
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

    public function __call($function, $args)
    {
        if ($function === 'empty') {
            return $this->isEmpty(current($args));
        }
        if (!in_array($function, $this->options->allowed_functions)) {
            $function = array($this->plugins, $function);
        }
        return call_user_func_array($function, $args);
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

    public function getByKey($structure, $key)
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

    public function template($template_name, array $args)
    {
        $template = $this->loader->load($template_name);
        $template->set($args);
        echo $template->render();
    }

    public function listArrayElements($array, $template = null)
    {
        if ($array instanceof ListAdapter) {

        } elseif (is_array($array) || $array instanceof Traversable) {
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

    public function __get($key)
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
