<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler;

use Modules\Templating\Compiler\Exceptions\CompileException;
use Modules\Templating\Compiler\Extensions\Core;
use Modules\Templating\Extension;
use Modules\Templating\TemplatingOptions;

class Environment
{
    private $tags;
    private $binary_operators;
    private $prefix_unary_operators;
    private $postfix_unary_operators;
    private $functions;
    private $options;
    private $extensions;

    public function __construct(TemplatingOptions $options)
    {
        $this->extensions              = array();
        $this->functions               = array();
        $this->binary_operators        = new OperatorCollection();
        $this->prefix_unary_operators  = new OperatorCollection();
        $this->postfix_unary_operators = new OperatorCollection();
        $this->options                 = $options;
        $this->addExtension(new Core());
    }

    public function getOptions()
    {
        return $this->options;
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
            throw new CompileException('Extension not found: ' . $name);
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
            throw new CompileException('Function not found: ' . $name);
        }
        return $this->functions[$name];
    }

    public function getFunctions()
    {
        return $this->functions;
    }

    public function hasFunction($name)
    {
        return isset($this->functions[$name]);
    }

    public function addTag(Tag $tag)
    {
        $name              = $tag->getTag();
        $this->tags[$name] = $tag;
    }

    public function getBinaryOperators()
    {
        return $this->binary_operators;
    }

    public function getUnaryPrefixOperators()
    {
        return $this->prefix_unary_operators;
    }

    public function getUnaryPostfixOperators()
    {
        return $this->postfix_unary_operators;
    }

    public function getTags()
    {
        return $this->tags;
    }

    public function getOperatorSymbols()
    {
        return array_merge(
                $this->binary_operators->getSymbols(),
                $this->prefix_unary_operators->getSymbols(),
                $this->postfix_unary_operators->getSymbols()
        );
    }
}
