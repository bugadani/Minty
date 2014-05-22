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
        $cached = $this->getCachePath($template);

        if (!$this->loader->exists($template)) {
            $this->log('Template not found: %s', $template);
            throw new RuntimeException("Template not found: {$template}");
        }
        if ($this->loader->isCacheFresh($template)) {
            return;
        }

        $compiler = $this->environment->getCompiler();

        $this->log('Compiling %s', $template);
        $this->compileFile($template, $cached);

        //Fetch before line 77 overrides it
        $embeddedTemplateNames = $compiler->getEmbeddedTemplateNames();
        if ($compiler->extendsTemplate()) {
            $this->compileIfNeeded($compiler->getExtendedTemplate());
        }
        foreach ($embeddedTemplateNames as $template) {
            $this->compileIfNeeded($template);
        }
    }

    /**
     * @param $template
     * @param $class
     *
     * @return mixed
     */
    private function compileString($template, $class)
    {
        $stream = $this->environment->getTokenizer()->tokenize($template);
        $node   = $this->environment->getParser()->parse($stream);
        $this->environment->getNodeTreeTraverser()->traverse($node);

        return $this->environment->getCompiler()->compile($node, $class);
    }

    private function compileErrorTemplate(TemplatingException $e, $template, $source, $class)
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

        $closingTagPrefix = $this->environment->getOption('block_end_prefix', 'end');
        $baseTemplate = $this->environment->getOption(
            'error_template',
            '__compile_error_template'
        );

        //escape string delimiters in exception message
        $message = strtr($e->getMessage(), array("'" => "\\'"));

        //insert data into template and decorate with inheritance code
        //(error message, error line number, first displayed line number, template source)

        $source = "{extends '{$baseTemplate}'}" .
            "{block error}" .
            "{templateName: '{$template}'}" .
            "{message: '{$message}'}" .
            "{lines: [{$lineArray}]}" .
            "{errorLine: {$errLine}}" .
            "{firstLine: {$firstLine}}".
            "{parent}{{$closingTagPrefix}block}";

        return $this->compileString($source, $class);
    }

    /**
     * @param $template
     * @param $cached
     */
    private function compileFile($template, $cached)
    {
        //Get the desired class name for the template
        $class = $this->loader->getTemplateClassName($template);
        $this->log('Compiled class name is %s', $class);

        //Read the template
        $templateSource = $this->loader->load($template);

        //Compile and optimize the template
        try {
            $compiled = $this->compileString($templateSource, $class);
        } catch (TemplatingException $e) {
            $compiled = $this->compileErrorTemplate($e, $template, $templateSource, $class);
        }
        //Store the compiled template
        $cacheDir = dirname($cached);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }
        file_put_contents($cached, $compiled);
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

        $this->setGlobals($object);

        return $object;
    }

    public function setGlobals(Template $template)
    {
        $template->set($this->environment->getOption('global_variables'));
    }

    private function getCachePath($template)
    {
        return sprintf(
            $this->environment->getOption('cache_path'),
            $this->loader->getCacheKey($template)
        );
    }
}
