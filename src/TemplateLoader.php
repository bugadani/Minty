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

        //Compile and store the template
        $this->saveCompiledTemplate(
            $this->compileTemplateByName($template),
            $this->getCachePath($template)
        );
    }

    /**
     * @param $template
     *
     * @return string
     */
    private function compileTemplateByName($template)
    {
        $this->log('Compiling %s', $template);
        //Get the desired class name for the template
        $class = $this->loader->getTemplateClassName($template);
        $this->log('Compiled class name is %s', $class);

        //Read the template
        $templateSource = $this->loader->load($template);

        //Compile and optimize the template
        try {
            $compiled = $this->compileString($templateSource, $class);
        } catch (TemplatingException $e) {
            $this->log('Failed to compile %s. Reason: %s.', $template, $e->getMessage());
            $this->log('Compiling an error template.', $template);
            $templateSource = $this->getErrorTemplate($e, $template, $templateSource);
            $compiled       = $this->compileString($templateSource, $class);
        }

        return $compiled;
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
     * @param $class
     *
     * @return mixed
     */
    private function compileString($template, $class)
    {
        //Tokenize and parse the template
        $stream = $this->environment->getTokenizer()->tokenize($template);
        $node   = $this->environment->getParser()->parse($stream);

        //Run the Node Tree Visitors
        $this->environment->getNodeTreeTraverser()->traverse($node);

        //Compile the template
        $compiler = $this->environment->getCompiler();
        $compiled = $compiler->compile($node, $class);

        //Fetch template names related to the current (extended, embedded templates)
        $relatedTemplateNames = $compiler->getEmbeddedTemplateNames();
        if ($compiler->extendsTemplate()) {
            $relatedTemplateNames[] = $compiler->getExtendedTemplate();
        }

        //Compile the related templates
        foreach ($relatedTemplateNames as $template) {
            $this->compileIfNeeded($template);
        }

        return $compiled;
    }

    private function getErrorTemplate(TemplatingException $e, $template, $source)
    {
        $errLine     = $e->getSourceLine() - 1;
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
        $message = strtr($e->getMessage(), array("'" => "\\'"));

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

        $object->loadGlobals();

        return $object;
    }

    private function getCachePath($template)
    {
        return sprintf(
            $this->environment->getOption('cache_path'),
            $this->loader->getCacheKey($template)
        );
    }
}
