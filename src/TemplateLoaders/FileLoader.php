<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\TemplateLoaders;

use Modules\Templating\AbstractTemplateLoader;
use Modules\Templating\Environment;

class FileLoader extends AbstractTemplateLoader
{
    /**
     * @var Environment
     */
    private $environment;

    public function __construct(Environment $environment)
    {
        $this->environment = $environment;
    }

    private function getPath($template)
    {
        $directory = $this->environment->getOption('template_directory', 'templates');
        $extension = $this->environment->getOption('template_extension', 'tpl');

        return "{$directory}/{$template}.{$extension}";
    }

    public function isCacheFresh($template)
    {
        $cachePath = sprintf(
            $this->environment->getOption('cache_path'),
            $this->getCacheKey($template)
        );

        if (!is_file($cachePath)) {
            return false;
        }

        if (!$this->environment->getOption('reload', false)) {
            return true;
        }

        return filemtime($this->getPath($template)) < filemtime($cachePath);

    }

    public function exists($template)
    {
        return is_file($this->getPath($template));
    }

    public function load($template)
    {
        return file_get_contents($this->getPath($template));
    }

    public function getTemplateClassName($template)
    {
        $path = $this->environment->getOption('cache_namespace');
        $path .= '\\' . strtr($template, '/', '\\');

        $pos       = strrpos($path, '\\') + 1;
        $className = substr($path, $pos);
        $namespace = substr($path, 0, $pos);

        return '\\' . $namespace . 'Template_' . $className;
    }

    public function getCacheKey($template)
    {
        $fullyQualifiedName = $this->getTemplateClassName($template);
        $namespaceLength    = strlen($this->environment->getOption('cache_namespace'));

        $className = substr($fullyQualifiedName, $namespaceLength + 2);

        return strtr($className, '\\', '/');
    }
}
