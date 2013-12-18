<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler;

use Modules\Templating\Compiler\Exceptions\CompileException;
use Modules\Templating\Extension;
use Modules\Templating\Extensions\Core;
use Modules\Templating\TemplatingOptions;

class Environment
{
    /**
     * @var Tag[]
     */
    private $tags;

    /**
     * @var OperatorCollection
     */
    private $binary_operators;

    /**
     * @var OperatorCollection
     */
    private $unary_prefix_operators;

    /**
     * @var OperatorCollection
     */
    private $unary_postfix_operators;

    /**
     * @var TemplateFunction[]
     */
    private $functions;

    /**
     * @var TemplatingOptions
     */
    private $options;

    /**
     * @var Extension[]
     */
    private $extensions;

    /**
     * @param TemplatingOptions $options
     */
    public function __construct(TemplatingOptions $options)
    {
        $this->extensions              = array();
        $this->functions               = array();
        $this->binary_operators        = new OperatorCollection();
        $this->unary_prefix_operators  = new OperatorCollection();
        $this->unary_postfix_operators = new OperatorCollection();
        $this->options                 = $options;
        $this->addExtension(new Core());
    }

    /**
     * @return TemplatingOptions
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param Extension $extension
     */
    public function addExtension(Extension $extension)
    {
        $this->extensions[$extension->getExtensionName()] = $extension;
        $extension->registerExtension($this);
    }

    /**
     * @param TemplateFunction $function
     */
    public function addFunction(TemplateFunction $function)
    {
        $this->functions[$function->getFunctionName()] = $function;
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

    /**
     * @return TemplateFunction[]
     */
    public function getFunctions()
    {
        return $this->functions;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasFunction($name)
    {
        return isset($this->functions[$name]);
    }

    /**
     * @param Tag $tag
     */
    public function addTag(Tag $tag)
    {
        $this->tags[$tag->getTag()] = $tag;
    }

    /**
     * @return Tag[]
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * @return OperatorCollection
     */
    public function getBinaryOperators()
    {
        return $this->binary_operators;
    }

    /**
     * @return OperatorCollection
     */
    public function getUnaryPrefixOperators()
    {
        return $this->unary_prefix_operators;
    }

    /**
     * @return OperatorCollection
     */
    public function getUnaryPostfixOperators()
    {
        return $this->unary_postfix_operators;
    }

    /**
     * @return string[]
     */
    public function getOperatorSymbols()
    {
        return array_merge(
                $this->binary_operators->getSymbols(),
                $this->unary_prefix_operators->getSymbols(),
                $this->unary_postfix_operators->getSymbols()
        );
    }
}
