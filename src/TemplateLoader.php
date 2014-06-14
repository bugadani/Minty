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
use Modules\Templating\Compiler\Exceptions\TemplateNotFoundException;
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

    /**
     * @param string|array $template The template name
     *
     * @return string
     */
    public function getSource($template)
    {
        return $this->loader->load($template);
    }

    private function compileIfNeeded($templateName, $class)
    {
        $cacheEnabled = $this->environment->getOption('cache', false);
        if ($cacheEnabled) {
            if ($this->loader->isCacheFresh($templateName)) {
                //The template is already compiled and up to date
                return;
            }
        }

        $this->log('Compiling %s', $templateName);

        //Read the template
        $template = $this->getSource($templateName);

        try {
            $compiled = $this->environment->compileTemplate($template, $templateName, $class);
        } catch (TemplatingException $e) {
            $this->log('Failed to compile %s. Reason: %s.', $templateName, $e->getMessage());
            $this->log('Compiling an error template.', $templateName);
            $template = $this->getErrorTemplate($e, $templateName, $template);
            $compiled = $this->environment->compileTemplate($template, $templateName, $class);
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
        $destination = $this->environment->getCachePath(
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

        $this->log('Template not found: %s', $templates);
        throw new TemplateNotFoundException($templates);
    }

    /**
     * @param $template
     *
     * @return Template
     * @throws RuntimeException
     */
    public function load($template)
    {
        $template = $this->findFirstExistingTemplate($template);
        if (isset($this->loadedTemplates[$template])) {
            return $this->loadedTemplates[$template];
        }

        $class = $this->environment->getTemplateClassName(
            $this->loader->getCacheKey($template)
        );

        $this->log('Loading %s', $template);
        $this->compileIfNeeded($template, $class);

        /** @var $object Template */
        $object = new $class($this, $this->environment);

        $this->loadedTemplates[$template] = $object;

        return $object;
    }

    public function render($template, $variables = array())
    {
        $object = $this->load($template);
        $object->displayTemplate(
            $this->environment->createContext($variables)
        );
    }
}
