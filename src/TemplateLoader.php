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
        if ($compiler->extendsTemplate()) {
            $this->load($compiler->getExtendedTemplate());
        }
        foreach ($compiler->getEmbeddedTemplates() as $template) {
            $this->load($template['file']);
        }
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
        $stream = $this->environment->getTokenizer()->tokenize($templateSource);
        $node   = $this->environment->getParser()->parse($stream);
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
