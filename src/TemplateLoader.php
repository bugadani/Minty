<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating;

use Miny\Log\Log;
use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\NodeTreeOptimizer;
use RuntimeException;

class TemplateLoader
{
    /**
     * @var array
     */
    private $options;

    /**
     * @var Compiler
     */
    private $compiler;

    /**
     * @var NodeTreeOptimizer
     */
    private $nodeTreeOptimizer;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var Log
     */
    private $log;

    /**
     * @param Environment       $environment
     * @param Compiler          $compiler
     * @param NodeTreeOptimizer $nodeTreeOptimizer
     * @param Log|null          $log
     */
    public function __construct(
        Environment $environment,
        Compiler $compiler,
        NodeTreeOptimizer $nodeTreeOptimizer,
        Log $log = null
    ) {
        $this->compiler          = $compiler;
        $this->nodeTreeOptimizer = $nodeTreeOptimizer;
        $this->options           = $environment->getOptions();
        $this->environment       = $environment;
        $this->log               = $log;
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
        $className = $this->compiler->getClassForTemplate($file, false);

        return sprintf($this->options['cache_path'], dirname($file) . '/' . $className);
    }

    private function getPath($file)
    {
        return sprintf(
            $this->options['template_path'],
            $file,
            $this->options['template_extension']
        );
    }

    private function shouldReload($file, $cached)
    {
        if (!is_file($cached)) {
            return true;
        }

        return $this->options['reload'] && (filemtime($file) > filemtime($cached));
    }

    private function compileIfNeeded($template)
    {
        $cached = $this->getCachedPath($template);
        $file   = $this->getPath($template);

        if (!is_file($file)) {
            $this->log('File not found: ' . $file);
            throw new RuntimeException('Template file not found: ' . $file);
        }
        if (!$this->shouldReload($file, $cached)) {
            return;
        }

        $class = $this->compiler->getClassForTemplate($template);
        $this->log('Compiling %s into %s', $file, $cached);
        $this->log('Compiled class name is %s', $class);
        $this->compileFile($file, $class, $cached);
        if ($this->compiler->extendsTemplate()) {
            $this->load($this->compiler->getExtendedTemplate());
        }
        foreach ($this->compiler->getEmbeddedTemplates() as $template) {
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
        $contents = file_get_contents($file);
        $stream   = $this->environment->getTokenizer()->tokenize($contents);
        $node     = $this->environment->getParser()->parse($stream);

        $this->nodeTreeOptimizer->optimize($node);

        $compiled = $this->compiler->compile($node, $class);
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
        $class = '\\' . $this->compiler->getClassForTemplate($template);

        $this->compileIfNeeded($template);

        $this->log('Loading %s', $template);
        if (!class_exists($class)) {
            $file = $this->getCachedPath($template);
            $this->log('Template %s was not found in file %s', $class, $file);
            throw new RuntimeException('Template not found: ' . $template);
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
