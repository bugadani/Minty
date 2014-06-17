<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating;

use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\Exceptions\TemplateNotFoundException;
use Modules\Templating\Compiler\Exceptions\TemplatingException;
use Modules\Templating\Compiler\ExpressionParser;
use Modules\Templating\Compiler\FunctionCompiler;
use Modules\Templating\Compiler\NodeTreeTraverser;
use Modules\Templating\Compiler\NodeVisitor;
use Modules\Templating\Compiler\NodeVisitors\EnvironmentAwareNodeVisitor;
use Modules\Templating\Compiler\OperatorCollection;
use Modules\Templating\Compiler\Parser;
use Modules\Templating\Compiler\Tag;
use Modules\Templating\Compiler\TemplateFunction;
use Modules\Templating\Compiler\Tokenizer;
use Modules\Templating\TemplateLoaders\ChainLoader;
use Modules\Templating\TemplateLoaders\ErrorTemplateLoader;

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
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        $this->options = $options;
    }

    public function addTemplateLoader(AbstractTemplateLoader $loader)
    {
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

    public function compileTemplate($template, $templateName)
    {
        if (!isset($this->compiler)) {
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
        //Tokenize and parse the template
        $stream = $this->tokenizer->tokenize($template);
        $node   = $this->parser->parseTemplate($stream, $templateName);

        //Run the Node Tree Visitors
        $this->nodeTreeTraverser->traverse($node);

        //Compile the template
        return $this->compiler->compile($node);
    }

    public function getCachePath($cacheKey)
    {
        return $this->getOption('cache') . '/' . $cacheKey . '.php';
    }

    public function getTemplateClassName($template)
    {
        $cacheKey       = $this->loader->getCacheKey($template);
        $cacheNamespace = $this->getOption('cache_namespace', '');

        return $cacheNamespace . '\\' . strtr($cacheKey, '/', '\\');
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

    public function setOption($key, $value)
    {
        $this->options[$key] = $value;
    }

    /**
     * @param Extension $extension
     */
    public function addExtension(Extension $extension)
    {
        $this->extensions[$extension->getExtensionName()] = $extension;
        foreach ($extension->getFunctions() as $function) {
            $this->addFunction($function);
        }
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
        if($visitor instanceof EnvironmentAwareNodeVisitor) {
            $visitor->setEnvironment($this);
        }
        $this->nodeVisitors[] = $visitor;
    }

    public function createContext($variables = array())
    {
        if ($variables instanceof Context) {
            return $variables;
        }
        $context = new Context($this, $variables);
        $context->add($this->getOption('global_variables', array()));

        return $context;
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

    /**
     * @param string|array $template The template name
     *
     * @return string
     */
    public function getSource($template)
    {
        return $this->loader->load($template);
    }

    private function compileIfNeeded($templateName)
    {
        $cacheEnabled = $this->getOption('cache', false);
        if ($cacheEnabled) {
            if ($this->loader->isCacheFresh($templateName)) {
                //The template is already compiled and up to date
                return;
            }
        }

        //Read the template
        $template = $this->getSource($templateName);

        try {
            $compiled = $this->compileTemplate($template, $templateName);
        } catch (TemplatingException $e) {
            $template = $this->getErrorTemplate($e, $templateName, $template);
            $compiled = $this->compileTemplate($template, $templateName);
        }
        if ($cacheEnabled) {
            $this->saveCompiledTemplate($compiled, $templateName);
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

    private function getErrorTemplate(TemplatingException $exception, $template, $source)
    {
        $errLine     = $exception->getSourceLine() - 1;
        $firstLine   = max($errLine - 3, 0);
        $sourceLines = array_slice(explode("\n", $source), $firstLine, 7, true);

        $first     = true;
        $lineArray = '';
        //create a template-array for the lines
        foreach ($sourceLines as $lineNo => $line) {
            if ($first) {
                $first = false;
            } else {
                $lineArray .= ', ';
            }
            //escape string delimiters
            $line = strtr($line, array("'" => "\\'"));
            $lineArray .= "{$lineNo}: '{$line}'";
        }

        //escape string delimiters in exception message
        $message = strtr($exception->getMessage(), array("'" => "\\'"));

        $closingTagPrefix = $this->getOption('block_end_prefix', 'end');
        $baseTemplate     = $this->getOption('error_template', '__compile_error_template');

        //only load the error template loader when the default error template is requested
        if ($baseTemplate === '__compile_error_template' && !$this->errorTemplateLoaderLoaded) {
            $this->addTemplateLoader(
                new ErrorTemplateLoader($this)
            );
            $this->errorTemplateLoaderLoaded = true;
        }

        //insert data into template and decorate with inheritance code
        //(error message, error line number, first displayed line number, template source)

        return "{extends '{$baseTemplate}'}" .
        "{block error}" .
        "{\$templateName: '{$template}'}" .
        "{\$message: '{$message}'}" .
        "{\$lines: [{$lineArray}]}" .
        "{\$errorLine: {$errLine}}" .
        "{\$firstLine: {$firstLine}}" .
        "{parent}{{$closingTagPrefix}block}";
    }

    private function findFirstExistingTemplate($templates)
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

    /**
     * @param $template
     *
     * @return Template
     * @throws \RuntimeException
     */
    public function load($template)
    {
        $template = $this->findFirstExistingTemplate($template);
        if (isset($this->loadedTemplates[$template])) {
            return $this->loadedTemplates[$template];
        }

        $this->compileIfNeeded($template);

        /** @var $object Template */
        $class  = $this->getTemplateClassName($template);
        $object = new $class($this);

        $this->loadedTemplates[$template] = $object;

        return $object;
    }

    public function render($template, $variables = array())
    {
        $object = $this->load($template);
        $object->displayTemplate(
            $this->createContext($variables)
        );
    }
}
