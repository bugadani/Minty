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
use Modules\Templating\Compiler\Parser;
use Modules\Templating\Compiler\Tokenizer;
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
     * @var Environment
     */
    private $environment;

    /**
     * @var Log
     */
    private $log;

    /**
     * @var bool
     */
    private $initialized;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var Tokenizer
     */
    private $tokenizer;

    /**
     * @param Environment $environment
     * @param Compiler    $compiler
     * @param Log|null    $log
     */
    public function __construct(Environment $environment, Compiler $compiler, Log $log = null)
    {
        $this->compiler    = $compiler;
        $this->options     = $environment->getOptions();
        $this->environment = $environment;
        $this->log         = $log;
        $this->initialized = false;
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
        return sprintf($this->options['template_path'], $file);
    }

    private function shouldReload($file, $cached)
    {
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
        if (is_file($cached) && !$this->shouldReload($file, $cached)) {
            return;
        }

        if (!$this->initialized) {
            $this->initialized = true;
            $this->parser      = new Parser($this->environment);
            $this->tokenizer   = new Tokenizer($this->environment);
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
        $stream   = $this->tokenizer->tokenize($contents);
        $node     = $this->parser->parse($stream);
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
     * @throws \RuntimeException
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
        $object = new $class($this, $this->environment);

        $parent = $object->getParentTemplate();
        if ($parent) {
            $this->compileIfNeeded($parent);
        }
        foreach ($object->getEmbeddedTemplates() as $file) {
            $this->compileIfNeeded($file);
        }

        $object->set($this->options['global_variables']);

        return $object;
    }
}
