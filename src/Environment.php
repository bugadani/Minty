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
use Minty\Compiler\ExpressionTokenizer;
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
     * @var TemplateCacheInterface
     */
    private $templateCache;

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
    private $tags = [];

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
    private $functions = [];

    /**
     * @var array
     */
    private $options;

    /**
     * @var Extension[]
     */
    private $extensions = [];

    /**
     * @var FunctionCompiler[]
     */
    private $functionCompilers = [];

    /**
     * @var NodeVisitor[]
     */
    private $nodeVisitors = [];

    /**
     * @var NodeTreeTraverser
     */
    private $nodeTreeTraverser;

    /**
     * @var TemplateLoaderInterface
     */
    private $loader;

    /**
     * @var Template[]
     */
    private $loadedTemplates = [];

    /**
     * @var bool
     */
    private $errorTemplateLoaderLoaded;

    private $classMap = [];

    /**
     * @param TemplateLoaderInterface $loader
     * @param array                   $options
     */
    public function __construct(TemplateLoaderInterface $loader, array $options = [])
    {
        $default_options = [
            'autofilter'                  => 1,
            'block_end_prefix'            => '/',
            'cache'                       => false,
            'cache_namespace'             => '',
            'debug'                       => false,
            'default_autofilter_strategy' => 'html',
            'tag_consumes_newline'        => false,
            'delimiters'                  => [
                'tag'                    => ['{', '}'],
                'comment'                => ['{#', '#}'],
                'whitespace_control_tag' => ['{-', '-}']
            ],
            'error_template'              => '__compile_error_template',
            'fallback_tag'                => 'print',
            'global_variables'            => [],
            'strict_mode'                 => true,
            'template_base_class'         => 'Minty\\Template'
        ];

        $this->options = $options + $default_options;
        $this->options['delimiters'] += $default_options['delimiters'];

        if ($loader instanceof EnvironmentAwareInterface) {
            $loader->setEnvironment($this);
        }
        $this->loader = $loader;

        $this->createTemplateCache($this->options['cache']);

        if ($this->options['error_template'] !== '__compile_error_template') {
            //don't load the built-in error template loader
            $this->errorTemplateLoaderLoaded = true;
        } else {
            $this->errorTemplateLoaderLoaded = false;
        }
        spl_autoload_register([$this, 'autoloadTemplate'], true, true);
    }

    private function createTemplateCache($cache)
    {
        if ($cache) {
            if ($cache instanceof TemplateCacheInterface) {
                $this->templateCache = $cache;
            }
            $this->templateCache = new FileSystemCache($this->options['cache']);
        }
    }

    public function __destruct()
    {
        spl_autoload_unregister([$this, 'autoloadTemplate']);
    }

    /**
     * @throws \BadMethodCallException
     * @return TemplateCacheInterface
     */
    public function getTemplateCache()
    {
        if (!isset($this->templateCache)) {
            throw new \BadMethodCallException('Cache is disabled');
        }

        return $this->templateCache;
    }

    public function addTemplateLoader(TemplateLoaderInterface $loader)
    {
        if ($loader instanceof EnvironmentAwareInterface) {
            $loader->setEnvironment($this);
        }
        if ($this->loader instanceof ChainLoader) {
            $this->loader->addLoader($loader);
        } else {
            $this->loader = new ChainLoader($this->loader, $loader);
        }
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
        array_map(
            function (TemplateFunction $function) use ($extension) {
                $function->setExtension($extension);
                $this->addFunction($function);
            },
            $extension->getFunctions()
        );
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
     * @throws \OutOfBoundsException
     * @return Tag
     */
    public function getTag($tag)
    {
        if (!isset($this->tags[$tag])) {
            throw new \OutOfBoundsException("Tag {$tag} does not exist.");
        }

        return $this->tags[$tag];
    }

    /**
     * @param $tag
     *
     * @return bool
     */
    public function hasTag($tag)
    {
        return isset($this->tags[$tag]);
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
     * @throws \InvalidArgumentException
     * @return FunctionCompiler
     */
    public function getFunctionCompiler($class)
    {
        if (!isset($this->functionCompilers[$class])) {
            $this->functionCompilers[$class] = new $class;
            if (!$this->functionCompilers[$class] instanceof FunctionCompiler) {
                throw new \InvalidArgumentException("Class {$class} is not an instance of FunctionCompiler");
            }
        }

        return $this->functionCompilers[$class];
    }

    public function addNodeVisitor(NodeVisitor $visitor)
    {
        if ($visitor instanceof EnvironmentAwareInterface) {
            $visitor->setEnvironment($this);
        }
        $this->nodeVisitors[] = $visitor;
    }

    /**
     * @return OperatorCollection
     */
    public function getBinaryOperators()
    {
        return $this->binaryOperators;
    }

    /**
     * @return OperatorCollection
     */
    public function getUnaryPrefixOperators()
    {
        return $this->unaryPrefixOperators;
    }

    /**
     * @return OperatorCollection
     */
    public function getUnaryPostfixOperators()
    {
        return $this->unaryPostfixOperators;
    }

    /**
     * @return string[]
     */
    public function getOperatorSymbols()
    {
        return array_merge(
            $this->binaryOperators->getSymbols(),
            $this->unaryPrefixOperators->getSymbols(),
            $this->unaryPostfixOperators->getSymbols()
        );
    }

    private function initializeCompiler()
    {
        $this->binaryOperators       = new OperatorCollection();
        $this->unaryPrefixOperators  = new OperatorCollection();
        $this->unaryPostfixOperators = new OperatorCollection();

        foreach ($this->extensions as $ext) {
            $this->binaryOperators->addOperators($ext->getBinaryOperators());
            $this->unaryPrefixOperators->addOperators($ext->getPrefixUnaryOperators());
            $this->unaryPostfixOperators->addOperators($ext->getPostfixUnaryOperators());

            array_map([$this, 'addNodeVisitor'], $ext->getNodeVisitors());
            array_map([$this, 'addTag'], $ext->getTags());
        }

        $this->tokenizer         = new Tokenizer($this, new ExpressionTokenizer($this));
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

    private function autoloadTemplate($className)
    {
        if (!isset($this->classMap[$className])) {
            return;
        }

        $template = $this->classMap[$className];

        if ($this->options['cache']) {
            $cacheKey = $this->loader->getCacheKey($template);
            if (!$this->loader->isCacheFresh($template)) {
                $this->templateCache->save(
                    $cacheKey,
                    $this->compileTemplate($template)
                );
            }
            $this->templateCache->load($cacheKey);
        } else {
            evalCode($this->compileTemplate($template));
        }
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
        $cacheKey       = preg_replace('#[^\w/]+#', '_', $cacheKey);

        $className = strtr($cacheKey, '/', '\\');
        if (!empty($cacheNamespace)) {
            $className = $cacheNamespace . '\\' . $className;
        }
        $this->classMap[$className] = $template;

        return $className;
    }

    /**
     * @param $template
     *
     * @throws \Exception
     * @return Template
     */
    public function load($template)
    {
        $template = $this->findFirstExistingTemplate($template);
        if (!isset($this->loadedTemplates[$template])) {
            $class = $this->getTemplateClassName($template);

            $this->loadedTemplates[$template] = new $class($this);
            if (!$this->loadedTemplates[$template] instanceof Template) {
                throw new TemplatingException("The compiled class for {$template} is invalid");
            }
        }

        return $this->loadedTemplates[$template];
    }

    public function createContext($variables = [])
    {
        if ($variables instanceof Context) {
            return $variables;
        }

        //Local variables take precedence over globals
        return new Context(
            $this->options['strict_mode'],
            $variables + $this->options['global_variables']
        );
    }

    public function render($template, $variables = [])
    {
        try {
            $object = $this->load($template);
            $object->displayTemplate(
                $this->createContext($variables)
            );
        } catch (TemplatingException $e) {
            $errorTemplate = $this->options['error_template'];
            if ($errorTemplate === false) {
                throw $e;
            }
            if (!$this->errorTemplateLoaderLoaded) {
                $this->addTemplateLoader(
                    new ErrorTemplateLoader($this)
                );
                $this->errorTemplateLoaderLoaded = true;
            }
            $object = $this->load($errorTemplate);
            $object->displayTemplate(
                $this->createContext(
                    [
                        'templateName' => $template,
                        'exception'    => $e
                    ]
                )
            );
        }
    }
}

function evalCode($code)
{
    eval('?>' . $code);
}
