<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating;

use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\Exceptions\CompileException;
use Modules\Templating\Compiler\FunctionCompiler;
use Modules\Templating\Compiler\NodeTreeTraverser;
use Modules\Templating\Compiler\NodeVisitor;
use Modules\Templating\Compiler\OperatorCollection;
use Modules\Templating\Compiler\Parser;
use Modules\Templating\Compiler\Tag;
use Modules\Templating\Compiler\TemplateFunction;
use Modules\Templating\Compiler\Tokenizer;
use Modules\Templating\Extensions\Core;

class Environment
{
    /**
     * @var Tokenizer
     */
    private $tokenizer;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var Compiler
     */
    private $compiler;

    /**
     * @var Tag[]
     */
    private $tags = array();

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
    private $functions = array();

    /**
     * @var array
     */
    private $options;

    /**
     * @var Extension[]
     */
    private $extensions = array();

    /**
     * @var FunctionCompiler[]
     */
    private $functionCompilers = array();

    /**
     * @var NodeVisitor[]
     */
    private $nodeVisitors = array();

    /**
     * @var NodeTreeTraverser
     */
    private $nodeTreeTraverser;

    /**
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->binaryOperators       = new OperatorCollection();
        $this->unaryPrefixOperators  = new OperatorCollection();
        $this->unaryPostfixOperators = new OperatorCollection();
        $this->options               = $options;
        $this->addExtension(new Core());
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

    /**
     * @return Compiler
     */
    public function getCompiler()
    {
        if (!isset($this->compiler)) {
            $this->compiler = new Compiler($this);
        }

        return $this->compiler;
    }

    public function addGlobalVariable($name, $value)
    {
        $this->options['global_variables'][$name] = $value;
    }

    /**
     * @param $key
     * @param $default
     *
     * @throws \OutOfBoundsException
     * @return mixed
     */
    public function getOption($key, $default = null)
    {
        if (!isset($this->options[$key])) {
            if ($default === null) {
                throw new \OutOfBoundsException("Option {$key} is not set.");
            }

            return $default;
        }

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
            throw new CompileException("Extension not found: {$name}");
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
            throw new CompileException("Function not found: {$name}");
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
        $tagName              = $tag->getTag();
        $this->tags[$tagName] = $tag;
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
     * @return NodeTreeTraverser
     */
    public function getNodeTreeTraverser()
    {
        if (!isset($this->nodeTreeTraverser)) {
            $this->nodeTreeTraverser = new NodeTreeTraverser($this->getNodeVisitors());
        }

        return $this->nodeTreeTraverser;
    }

    public function getNodeVisitors()
    {
        if (empty($this->nodeVisitors)) {
            foreach ($this->extensions as $ext) {
                $ext->registerNodeVisitors($this);
            }
        }

        return $this->nodeVisitors;
    }

    public function addNodeVisitor(NodeVisitor $visitor)
    {
        $this->nodeVisitors[] = $visitor;
    }
}
