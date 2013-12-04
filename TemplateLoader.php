<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating;

use Miny\Log;
use Modules\Templating\Compiler\TemplateCompiler;
use RuntimeException;

class TemplateLoader
{
    /**
     * @var TemplatingOptions
     */
    private $options;

    /**
     * @var TemplateCompiler
     */
    private $compiler;
    private $loaded;

    /**
     * @var Plugins
     */
    private $plugins;

    /**
     * @var Log
     */
    private $log;

    /**
     *
     * @param TemplatingOptions $options
     * @param TemplateCompiler $compiler
     * @param Plugins $plugins
     * @param Log|null $log
     */
    public function __construct(TemplatingOptions $options, TemplateCompiler $compiler, Plugins $plugins,
                                Log $log = null)
    {
        $this->options  = $options;
        $this->compiler = $compiler;
        $this->plugins  = $plugins;
        $this->log      = $log;
        $this->loaded   = array();
    }

    protected function log($message)
    {
        if (isset($this->log)) {
            $args = func_get_args();
            array_shift($args);
            $this->log->debug('TemplateLoader: ' . $message, $args);
        }
    }

    private function getCachedPath($file)
    {
        return sprintf($this->options->cache_path, $file . 'Template');
    }

    private function getPath($file)
    {
        return sprintf($this->options->template_path, $file);
    }

    private function shouldReload($file, $cached)
    {
        return $this->options->reload && (filemtime($file) > filemtime($cached));
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
        $class    = $this->compiler->getClassForTemplate($template);
        $this->log('Compiling %s into %s', $file, $cached);
        $this->log('Compiled class name is %s', $class);
        $contents = file_get_contents($file);
        $compiled = $this->compiler->compile($contents, $class);
        if (!is_dir(dirname($cached))) {
            mkdir(dirname($cached), 0777, true);
        }
        file_put_contents($cached, $compiled);
        if ($this->compiler->extendsTemplate()) {
            $this->load($this->compiler->getExtendedTemplate());
        }
    }

    public function load($template)
    {
        $class     = $this->compiler->getClassForTemplate($template);
        $classname = '\\' . $class;

        $this->compileIfNeeded($template);

        $this->log('Loading %s', $template);
        if (!class_exists($classname)) {
            $file = $this->getCachedPath($template);
            $this->log('Template %s was not found in file %s', $classname, $file);
            throw new RuntimeException('Template not found: ' . $template);
        }
        $object = new $classname($this->options, $this, $this->plugins);
        $object->set($this->options->global_variables);
        return $object;
    }
}
