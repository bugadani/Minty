<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty;

use Minty\Compiler\Compiler;
use Minty\Compiler\Exceptions\TemplateNotFoundException;
use Minty\Compiler\Exceptions\TemplatingException;
use Minty\Compiler\ExpressionParser;
use Minty\Compiler\FunctionCompiler;
use Minty\Compiler\NodeTreeTraverser;
use Minty\Compiler\NodeVisitor;
use Minty\Compiler\OperatorCollection;
use Minty\Compiler\Parser;
use Minty\Compiler\Tag;
use Minty\Compiler\TemplateFunction;
use Minty\Compiler\Tokenizer;
use Minty\TemplateLoaders\ChainLoader;
use Minty\TemplateLoaders\ErrorTemplateLoader;

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
     * @var AbstractTemplateLoader|ChainLoader
     */
    private $loader;

    /**
     * @var Template[]
     */
    private $loadedTemplates = array();

    /**
     * @var bool
     */
    private $chainLoaderUsed = false;

    /**
     * @var bool
     */
    private $errorTemplateLoaderLoaded = false;

    /**
     * @param AbstractTemplateLoader $loader
     * @param array                  $options
     */
    public function __construct(AbstractTemplateLoader $loader, array $options = array())
    {
        $default_options = array(
            'autofilter'                  => 1,
            'block_end_prefix'            => '/',
            'cache'                       => false,
            'cache_namespace'             => '',
            'debug'                       => false,
            'default_autofilter_strategy' => 'html',
            'delimiters'                  => array(
                'tag'     => array('{', '}'),
                'comment' => array('{#', '#}')
            ),
            'error_template'              => '__compile_error_template',
            'fallback_tag'                => 'print',
            'global_variables'            => array(),
            'strict_mode'                 => true,
            'template_base_class'         => 'Minty\\Template'
        );
        $this->options   = array_merge($default_options, $options);

        $this->addTemplateLoader($loader);
        if ($this->options['error_template'] !== '__compile_error_template') {
            //don't load the built-in error template loader
            $this->errorTemplateLoaderLoaded = true;
        }
    }

    public function addTemplateLoader(AbstractTemplateLoader $loader)
    {
        if ($loader instanceof iEnvironmentAware) {
            $loader->setEnvironment($this);
        }
        if (!$this->chainLoaderUsed) {
            if (!isset($this->loader)) {
                $this->loader          = $loader;
                $this->chainLoaderUsed = $loader instanceof ChainLoader;

                return;
            } else {
                $oldLoader    = $this->loader;
                $this->loader = new ChainLoader();
                $this->loader->addLoader($oldLoader);
                $this->chainLoaderUsed = true;
            }
        }
        $this->loader->addLoader($loader);
    }

    public function getCachePath($cacheKey)
    {
        return $this->options['cache'] . '/' . $cacheKey . '.php';
    }

    public function addGlobalVariable($name, $value)
    {
        $this->options['global_variables'][$name] = $value;
    }

    /**
     * @param $key
     *
     * @throws \OutOfBoundsException
     * @return mixed
     */
    public function getOption($key)
    {
        if (!isset($this->options[$key])) {
            throw new \OutOfBoundsException("Option {$key} is not set.");
        }

        return $this->options[$key];
    }

    /**
     * @param Extension $extension
     */
    public function addExtension(Extension $extension)
    {
        $this->extensions[$extension->getExtensionName()] = $extension;
        foreach ($extension->getFunctions() as $function) {
            $function->setExtension($extension);
            $this->addFunction($function);
        }
    }

    /**
     * @param TemplateFunction $function
     *
     * @throws \InvalidArgumentException
     */
    public function addFunction(TemplateFunction $function)
    {
        $functionName = $function->getFunctionName();
        if (isset($this->functions[$functionName])) {
            $extension = $this->functions[$functionName]->getExtension()->getExtensionName();
            $message   = "Function {$functionName} is already registered";
            if ($extension) {
                $message .= " in extension '{$extension}'";
            }
            throw new \InvalidArgumentException($message);
        }
        $this->functions[$functionName] = $function;
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
     * @param $tag
     *
     * @return Tag
     */
    public function getTag($tag)
    {
        return $this->tags[$tag];
    }

    /**
     * @return Tag[]
     */
    public function getTags()
    {
        return $this->tags;
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

    public function addNodeVisitor(NodeVisitor $visitor)
    {
        if ($visitor instanceof iEnvironmentAware) {
            $visitor->setEnvironment($this);
        }
        $this->nodeVisitors[] = $visitor;
    }

    /**
     * @return OperatorCollection
     */
    public function getBinaryOperators()
    {
        if (!isset($this->binaryOperators)) {
            $this->initOperators();
        }

        return $this->binaryOperators;
    }

    /**
     * @return OperatorCollection
     */
    public function getUnaryPrefixOperators()
    {
        if (!isset($this->unaryPrefixOperators)) {
            $this->initOperators();
        }

        return $this->unaryPrefixOperators;
    }

    /**
     * @return OperatorCollection
     */
    public function getUnaryPostfixOperators()
    {
        if (!isset($this->unaryPostfixOperators)) {
            $this->initOperators();
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

    private function initOperators()
    {
        $this->binaryOperators       = new OperatorCollection();
        $this->unaryPrefixOperators  = new OperatorCollection();
        $this->unaryPostfixOperators = new OperatorCollection();

        foreach ($this->extensions as $ext) {
            $this->binaryOperators->addOperators($ext->getBinaryOperators());
            $this->unaryPrefixOperators->addOperators($ext->getPrefixUnaryOperators());
            $this->unaryPostfixOperators->addOperators($ext->getPostfixUnaryOperators());
        }
    }

    private function initializeCompiler()
    {
        foreach ($this->extensions as $ext) {
            foreach ($ext->getNodeVisitors() as $visitor) {
                $this->addNodeVisitor($visitor);
            }
            foreach ($ext->getTags() as $tag) {
                $this->addTag($tag);
            }
        }

        $this->tokenizer         = new Tokenizer($this);
        $this->parser            = new Parser($this, new ExpressionParser($this));
        $this->nodeTreeTraverser = new NodeTreeTraverser($this->nodeVisitors);
        $this->compiler          = new Compiler($this);
    }

    /**
     * @param string|array $template The template name
     *
     * @return string
     */
    public function getSource($template)
    {
        return $this->loader->load($template);
    }

    private function compileTemplate($template)
    {
        if (!isset($this->compiler)) {
            $this->initializeCompiler();
        }
        $templateSource = $this->getSource($template);

        $stream = $this->tokenizer->tokenize($templateSource);
        $node   = $this->parser->parseTemplate($stream, $template);

        $this->nodeTreeTraverser->traverse($node);

        return $this->compiler->compile($node);
    }

    private function compileIfNeeded($template)
    {
        $cacheEnabled = $this->options['cache'];
        if ($cacheEnabled) {
            if ($this->loader->isCacheFresh($template)) {
                //The template is already compiled and up to date
                return;
            }
        }

        $compiled = $this->compileTemplate($template);

        if ($cacheEnabled) {
            $this->saveCompiledTemplate($compiled, $template);
        } else {
            eval('?>' . $compiled);
        }
    }

    /**
     * @param $compiled
     * @param $templateName
     */
    private function saveCompiledTemplate($compiled, $templateName)
    {
        $destination = $this->getCachePath(
            $this->loader->getCacheKey($templateName)
        );

        $cacheDir = dirname($destination);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }
        file_put_contents($destination, $compiled);
    }

    public function findFirstExistingTemplate($templates)
    {
        if (is_array($templates)) {
            foreach ($templates as $template) {
                if ($this->loader->exists($template)) {
                    return $template;
                }
            }
            $templates = implode(', ', $templates);
        } elseif ($this->loader->exists($templates)) {
            return $templates;
        }

        throw new TemplateNotFoundException($templates);
    }

    public function getTemplateClassName($template)
    {
        $cacheKey       = $this->loader->getCacheKey($template);
        $cacheNamespace = $this->options['cache_namespace'];
        $cacheKey       = preg_replace('/[^\w\\/]+/', '_', $cacheKey);

        return $cacheNamespace . '\\' . strtr($cacheKey, '/', '\\');
    }

    /**
     * @param $template
     *
     * @return Template
     */
    public function load($template)
    {
        $template = $this->findFirstExistingTemplate($template);
        if (!isset($this->loadedTemplates[$template])) {
            $this->compileIfNeeded($template);

            $class                            = $this->getTemplateClassName($template);
            $this->loadedTemplates[$template] = new $class($this);
        }

        return $this->loadedTemplates[$template];
    }

    public function createContext($variables = array())
    {
        if ($variables instanceof Context) {
            return $variables;
        }

        //Add the globals first so locals can override them
        $context = new Context($this, $this->options['global_variables']);
        $context->add($variables);

        return $context;
    }

    public function render($template, $variables = array())
    {
        try {
            $object = $this->load($template);
            $object->displayTemplate(
                $this->createContext($variables)
            );
        } catch (TemplatingException $e) {
            if (!$this->errorTemplateLoaderLoaded) {
                $this->addTemplateLoader(
                    new ErrorTemplateLoader($this)
                );
                $this->errorTemplateLoaderLoaded = true;
            }
            $object = $this->load($this->options['error_template']);
            $object->displayTemplate(
                $this->createContext(
                    array(
                        'templateName' => $template,
                        'exception'    => $e
                    )
                )
            );
        }
    }
}
