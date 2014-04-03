<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating;

use Modules\Templating\Compiler\Exceptions\CompileException;
use Modules\Templating\Compiler\FunctionCompiler;
use Modules\Templating\Compiler\OperatorCollection;
use Modules\Templating\Compiler\Parser;
use Modules\Templating\Compiler\Tag;
use Modules\Templating\Compiler\TemplateFunction;
use Modules\Templating\Compiler\Tokenizer;
use Modules\Templating\Extensions\Core;

class Environment
{
    /**
     * @var Tag[]
     */
    private $tags;

    /**
     * @var OperatorCollection
     */
    private $binaryOperators;

    /**
     * @var OperatorCollection
     */
    private $unaryPrefixOperators;

    /**
     * @var OperatorCollection
     */
    private $unaryPostfixOperators;

    /**
     * @var TemplateFunction[]
     */
    private $functions;

    /**
     * @var array
     */
    private $options;

    /**
     * @var Extension[]
     */
    private $extensions;

    /**
     * @var FunctionCompiler[]
     */
    private $functionCompilers;

    /**
     * @var Tokenizer
     */
    private $tokenizer;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @param array $options
     */
    public function __construct($options)
    {
        //debug_print_backtrace();
        $this->extensions            = array();
        $this->functions             = array();
        $this->functionCompilers     = array();
        $this->tags                  = array();
        $this->binaryOperators       = new OperatorCollection();
        $this->unaryPrefixOperators  = new OperatorCollection();
        $this->unaryPostfixOperators = new OperatorCollection();
        $this->options               = $options;
        $this->addExtension(new Core());
    }

    public function addGlobalVariable($name, $value)
    {
        $this->options['global_variables'][$name] = $value;
    }

    /**
     * @param $key
     *
     * @return mixed
     */
    public function getOption($key)
    {
        return $this->options[$key];
    }

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
        $extension->registerFunctions($this);
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
     *
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
     *
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
     *
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
        if (empty($this->tags)) {
            foreach ($this->extensions as $ext) {
                $ext->registerTags($this);
            }
        }

        return $this->tags;
    }

    /**
     * @return OperatorCollection
     */
    public function getBinaryOperators()
    {
        if ($this->binaryOperators->isEmpty()) {
            foreach ($this->extensions as $ext) {
                $ext->registerBinaryOperators($this->binaryOperators);
            }
        }

        return $this->binaryOperators;
    }

    /**
     * @return OperatorCollection
     */
    public function getUnaryPrefixOperators()
    {
        if ($this->unaryPrefixOperators->isEmpty()) {
            foreach ($this->extensions as $ext) {
                $ext->registerUnaryPrefixOperators($this->unaryPrefixOperators);
            }
        }

        return $this->unaryPrefixOperators;
    }

    /**
     * @return OperatorCollection
     */
    public function getUnaryPostfixOperators()
    {
        if ($this->unaryPostfixOperators->isEmpty()) {
            foreach ($this->extensions as $ext) {
                $ext->registerUnaryPostfixOperators($this->unaryPostfixOperators);
            }
        }

        return $this->unaryPostfixOperators;
    }

    /**
     * @return string[]
     */
    public function getOperatorSymbols()
    {
        return array_merge(
            $this->getBinaryOperators()->getSymbols(),
            $this->getUnaryPrefixOperators()->getSymbols(),
            $this->getUnaryPostfixOperators()->getSymbols()
        );
    }

    /**
     * @param string $class
     *
     * @return FunctionCompiler
     */
    public function getFunctionCompiler($class)
    {
        if (!isset($this->functionCompilers[$class])) {
            $this->functionCompilers[$class] = new $class;
        }

        return $this->functionCompilers[$class];
    }

    /**
     * @return Tokenizer
     */
    public function getTokenizer()
    {
        if (!isset($this->tokenizer)) {
            $this->tokenizer = new Tokenizer($this);
        }

        return $this->tokenizer;
    }

    /**
     * @return Parser
     */
    public function getParser()
    {
        if (!isset($this->parser)) {
            $this->parser = new Parser($this);
        }

        return $this->parser;
    }
}
