<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating;

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

    private $parentTemplate = false;
    private $parentOf;

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
                throw new \BadMethodCallException("Filter is not found for {$for}");
        }
    }

    public function hasMethod($object, $method)
    {
        if (!is_object($object)) {
            throw new \UnexpectedValueException('Variable is not an object.');
        }

        return method_exists($object, $method);
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
        if ($haystack instanceof \Traversable) {
            $haystack = iterator_to_array($haystack);
        }
        if (is_array($haystack)) {
            return in_array($needle, $haystack);
        }
        throw new \InvalidArgumentException('The in keyword expects an array, a string or a Traversable instance');
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

    public function getEmbeddedTemplates()
    {
        return array();
    }

    public function renderBlock($blockName, Context $context, $parentBlock = false)
    {
        $methodName = $blockName . 'Block';

        if ($this->parentOf) {
            $self = $this->parentOf;
        } else {
            $self = $this;
        }

        if (!method_exists($self, $methodName) || $parentBlock) {
            if ($this->parentOf) {
                $parent = $this;
            } else {
                $parent = $this->loader->load($this->parentTemplate);
            }
            if (method_exists($parent, $methodName)) {
                $parent->$methodName($context);
            }
        } else {
            $self->$methodName($context);
        }
    }

    public function render(Context $context)
    {
        $parent           = $this->loader->load($this->parentTemplate);
        $oldParentOf      = $parent->parentOf;
        $parent->parentOf = $this;
        $parent->render($context);
        $parent->parentOf = $oldParentOf;
    }
}
