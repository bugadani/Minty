<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating;

use Miny\Log\AbstractLog;
use Miny\Log\Log;
use Modules\Templating\Compiler\Exceptions\TemplatingException;
use Modules\Templating\Compiler\Nodes\ClassNode;
use RuntimeException;

class TemplateLoader
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var AbstractLog
     */
    private $log;

    /**
     * @var AbstractTemplateLoader
     */
    private $loader;

    /**
     * @var Template[]
     */
    private $loadedTemplates = array();

    /**
     * @param Environment            $environment
     * @param AbstractTemplateLoader $loader
     * @param AbstractLog|null       $log
     */
    public function __construct(
        Environment $environment,
        AbstractTemplateLoader $loader,
        AbstractLog $log = null
    ) {
        $this->environment = $environment;
        $this->loader      = $loader;
        $this->log         = $log;
    }

    protected function log($message)
    {
        if (!isset($this->log)) {
            return;
        }
        $args = array_slice(func_get_args(), 1);
        $this->log->write(Log::DEBUG, 'TemplateLoader', $message, $args);
    }

    private function compileIfNeeded($template)
    {
        if (!$this->loader->exists($template)) {
            $this->log('Template %s is not found', $template);
            throw new RuntimeException("Template not found: {$template}");
        }
        if ($this->loader->isCacheFresh($template)) {
            //The template is already compiled and up to date
            return;
        }

        $this->compileTemplateByName($template);
    }

    /**
     * @param $template
     *
     * @return string
     */
    private function compileTemplateByName($template)
    {
        $this->log('Compiling %s', $template);

        //Read the template
        $templateSource = $this->loader->load($template);

        //Compile and optimize the template
        try {
            $this->compileString($templateSource, $template);
        } catch (TemplatingException $e) {
            $this->log('Failed to compile %s. Reason: %s.', $template, $e->getMessage());
            $this->log('Compiling an error template.', $template);
            $templateSource = $this->getErrorTemplate($e, $template, $templateSource);
            $this->compileString($templateSource, $template);
        }
    }

    /**
     * @param $compiled
     * @param $destination
     */
    private function saveCompiledTemplate($compiled, $destination)
    {
        $cacheDir = dirname($destination);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }
        file_put_contents($destination, $compiled);
    }

    /**
     * @param $template
     * @param $templateName
     *
     * @return mixed
     */
    private function compileString($template, $templateName)
    {
        //Get the desired class name for the template
        $class = $this->loader->getTemplateClassName($templateName);
        $this->log('Compiled class name is %s', $class);

        //Tokenize and parse the template
        $stream = $this->environment->getTokenizer()->tokenize($template);
        $node   = $this->environment->getParser()->parseTemplate($stream, $templateName);

        //Run the Node Tree Visitors
        $this->environment->getNodeTreeTraverser()->traverse($node);

        /** @var $relatedTemplates ClassNode[] */
        $relatedTemplates = $node->getChildren();
        $relatedNames     = array(
            $relatedTemplates[0]->getTemplateName()
        );

        foreach ($relatedTemplates as $related) {
            if ($related->hasParentTemplate()) {
                $relatedNames[] = $related->getParentTemplate();
            }
        }

        //Compile the template
        $compiler = $this->environment->getCompiler();
        $compiled = $compiler->compile($node, $class);

        //Compile and store the template
        $this->saveCompiledTemplate($compiled, $this->getCachePath($templateName));

        //Compile the related templates
        foreach (array_unique($relatedNames) as $template) {
            $this->compileIfNeeded($template);
        }
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

        $closingTagPrefix = $this->environment->getOption(
            'block_end_prefix',
            'end'
        );
        $baseTemplate     = $this->environment->getOption(
            'error_template',
            '__compile_error_template'
        );

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

    /**
     * @param $template
     *
     * @return Template
     * @throws RuntimeException
     */
    public function load($template)
    {
        if(isset($this->loadedTemplates[$template])) {
            return $this->loadedTemplates[$template];
        }
        $class = $this->loader->getTemplateClassName($template);

        $this->log('Loading %s', $template);
        $this->compileIfNeeded($template);

        if (!class_exists($class)) {
            $file = $this->getCachePath($template);
            $this->log('Template %s was not found in file %s', $class, $file);
            throw new RuntimeException("Template not found: {$template}");
        }

        /** @var $object Template */
        $object = new $class($this, $this->environment);

        $parent = $object->getParentTemplate();
        if ($parent) {
            $this->compileIfNeeded($parent);
        }
        foreach ($object->getEmbeddedTemplates() as $file) {
            $this->compileIfNeeded($file);
        }
        $this->loadedTemplates[$template] = $object;

        return $object;
    }

    public function render($template, $variables = array())
    {
        $object = $this->load($template);
        $object->render(
            $this->createContext($variables)
        );
    }

    private function getCachePath($template)
    {
        return sprintf(
            $this->environment->getOption('cache_path'),
            $this->loader->getCacheKey($template)
        );
    }

    public function createContext($variables = array())
    {
        $context = new Context($this->environment, $variables);
        $context->add($this->environment->getOption('global_variables'));

        return $context;
    }
}
