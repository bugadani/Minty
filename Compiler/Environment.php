<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler;

use Modules\Templating\Compiler\Extensions\Core;
use Modules\Templating\Extension;
use Modules\Templating\TemplatingOptions;

class Environment
{
    private $blocks;
    private $tags;
    private $operators;
    private $functions;
    private $options;
    private $extensions;

    public function __construct(TemplatingOptions $options)
    {
        $this->extensions = array();
        $this->functions  = array();
        $this->options    = $options;
        $this->addExtension(new Core());
    }

    public function addExtension(Extension $extension)
    {
        $this->extensions[$extension->getExtensionName()] = $extension;
        $extension->registerExtension($this);
    }

    public function addFunction(TemplateFunction $function)
    {
        $name                   = $function->getFunctionName();
        $this->functions[$name] = $function;
    }

    /**
     * @param string $name
     * @return Extension
     * @throws CompileException
     */
    public function getExtension($name)
    {
        if (!isset($this->extensions[$name])) {
            throw new CompileException('Extension not found:' . $name);
        }
        return $this->extensions[$name];
    }

    /**
     * @param string $name
     * @return TemplateFunction
     * @throws CompileException
     */
    public function getFunction($name)
    {
        if (!isset($this->functions[$name])) {
            throw new CompileException('Functions not found:' . $name);
        }
        return $this->functions[$name];
    }

    public function hasFunction($name)
    {
        return isset($this->functions[$name]);
    }

    public function getFunctions()
    {
        return $this->functions;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function addTag(Tag $tag)
    {
        $this->tags[] = $tag;
    }

    public function addOperator(Operator $operator)
    {
        $this->operators[] = $operator;
    }

    public function blocks()
    {
        return $this->blocks;
    }

    public function tags()
    {
        return $this->tags;
    }

    public function operators()
    {
        return $this->operators;
    }
}
