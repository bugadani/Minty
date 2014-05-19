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
     * @param Environment      $environment
     * @param AbstractLog|null $log
     */
    public function __construct(Environment $environment, AbstractLog $log = null)
    {
        $this->environment = $environment;
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

    private function getCachedPath($file)
    {
        $className = $this->environment->getCompiler()->getClassForTemplate($file, false);

        return sprintf($this->environment->getOption('cache_path'), dirname($file) . '/' . $className);
    }

    private function getPath($file)
    {
        return sprintf(
            $this->environment->getOption('template_path'),
            $file,
            $this->environment->getOption('template_extension')
        );
    }

    private function shouldReload($file, $cached)
    {
        if (!is_file($cached)) {
            return true;
        }

        return $this->environment->getOption('reload') && (filemtime($file) > filemtime($cached));
    }

    private function compileIfNeeded($template)
    {
        $cached = $this->getCachedPath($template);
        $file   = $this->getPath($template);

        if (!is_file($file)) {
            $this->log('File not found: %s', $file);
            throw new RuntimeException("Template file not found: {$file}");
        }
        if (!$this->shouldReload($file, $cached)) {
            return;
        }

        $compiler = $this->environment->getCompiler();

        $class = $compiler->getClassForTemplate($template);
        $this->log('Compiling %s into %s', $file, $cached);
        $this->log('Compiled class name is %s', $class);
        $this->compileFile($file, $class, $cached);
        if ($compiler->extendsTemplate()) {
            $this->load($compiler->getExtendedTemplate());
        }
        foreach ($compiler->getEmbeddedTemplates() as $template) {
            $this->load($template['file']);
        }
    }

    /**
     * @param $file
     * @param $class
     * @param $cached
     */
    private function compileFile($file, $class, $cached)
    {
        //Read the template
        $contents = file_get_contents($file);

        //Compile and optimize the template
        $stream   = $this->environment->getTokenizer()->tokenize($contents);
        $node     = $this->environment->getParser()->parse($stream);
        $this->environment->getNodeTreeTraverser()->traverse($node);
        $compiled = $this->environment->getCompiler()->compile($node, $class);

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
        $class = '\\' . $this->environment->getCompiler()->getClassForTemplate($template);

        $this->compileIfNeeded($template);

        $this->log('Loading %s', $template);
        if (!class_exists($class)) {
            $file = $this->getCachedPath($template);
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
}
