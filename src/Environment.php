<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating;

use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\ExpressionParser;
use Modules\Templating\Compiler\FunctionCompiler;
use Modules\Templating\Compiler\NodeTreeTraverser;
use Modules\Templating\Compiler\NodeVisitor;
use Modules\Templating\Compiler\OperatorCollection;
use Modules\Templating\Compiler\Parser;
use Modules\Templating\Compiler\Tag;
use Modules\Templating\Compiler\TemplateFunction;
use Modules\Templating\Compiler\Tokenizer;

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
     * @var TemplateLoader
     */
    private $templateLoader;

    /**
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        $this->options = $options;
    }

    public function setTemplateLoader(TemplateLoader $templateLoader)
    {
        $this->templateLoader = $templateLoader;
    }

    /**
     * @return TemplateLoader
     */
    public function getTemplateLoader()
    {
        return $this->templateLoader;
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
            $this->parser = new Parser($this, new ExpressionParser($this));
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
     * @throws \RuntimeException
     * @return Extension
     */
    public function getExtension($name)
    {
        if (!isset($this->extensions[$name])) {
            throw new \RuntimeException("Extension not found: {$name}");
        }

        return $this->extensions[$name];
    }

    /**
     * @param string $name
     *
     * @throws \RuntimeException
     * @return TemplateFunction
     */
    public function getFunction($name)
    {
        if (!isset($this->functions[$name])) {
            throw new \RuntimeException("Function not found: {$name}");
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
        if (!isset($this->binaryOperators)) {
            $this->binaryOperators = new OperatorCollection();
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
        if (!isset($this->unaryPrefixOperators)) {
            $this->unaryPrefixOperators = new OperatorCollection();
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
        if (!isset($this->unaryPostfixOperators)) {
            $this->unaryPostfixOperators = new OperatorCollection();
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
