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
    private $chainLoaderUsed = false;

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
            'delimiters'                  => [
                'tag'     => ['{', '}'],
                'comment' => ['{#', '#}']
            ],
            'error_template'              => '__compile_error_template',
            'fallback_tag'                => 'print',
            'global_variables'            => [],
            'strict_mode'                 => true,
            'template_base_class'         => 'Minty\\Template'
        ];
        $this->options   = array_merge($default_options, $options);

        if ($loader instanceof EnvironmentAwareInterface) {
            $loader->setEnvironment($this);
        }
        $this->loader          = $loader;
        $this->chainLoaderUsed = $loader instanceof ChainLoader;

        if ($this->options['error_template'] !== '__compile_error_template') {
            //don't load the built-in error template loader
            $this->errorTemplateLoaderLoaded = true;
        } else {
            $this->errorTemplateLoaderLoaded = false;
        }

        spl_autoload_register([$this, 'autoloadTemplate']);
    }

    public function addTemplateLoader(TemplateLoaderInterface $loader)
    {
        if ($loader instanceof EnvironmentAwareInterface) {
            $loader->setEnvironment($this);
        }
        if (!$this->chainLoaderUsed) {
            $chainLoader = new ChainLoader();
            $chainLoader->addLoader($this->loader);
            $this->loader          = $chainLoader;
            $this->chainLoaderUsed = true;
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

    private function autoloadTemplate($className)
    {
        if (!isset($this->classMap[$className])) {
            return;
        }

        $template = $this->classMap[$className];

        $cacheEnabled = $this->options['cache'];

        if ($cacheEnabled) {
            if (!$this->loader->isCacheFresh($template)) {
                $compiled = $this->compileTemplate($template);
                $this->saveCompiledTemplate($compiled, $template);
            }
            includeFile(
                $this->getCachePath(
                    $this->loader->getCacheKey($template)
                )
            );
        } else {
            evalCode($this->compileTemplate($template));
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
        $cacheKey       = preg_replace('#[^\w/]+#', '_', $cacheKey);

        $className = $cacheNamespace . '\\' . strtr($cacheKey, '/', '\\');

        $this->classMap[$className] = $template;

        return $className;
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
            $class = $this->getTemplateClassName($template);

            $this->loadedTemplates[$template] = new $class($this);
        }

        return $this->loadedTemplates[$template];
    }

    public function createContext($variables = [])
    {
        if ($variables instanceof Context) {
            return $variables;
        }

        //Local variables take precedence over globals
        return new Context($this, $variables + $this->options['global_variables']);
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

function includeFile($file)
{
    include $file;
}

function evalCode($code)
{
    eval('?>' . $code);
}
