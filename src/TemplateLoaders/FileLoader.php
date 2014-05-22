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
        if ($template === '__compile_error_template') {
            return true;
        }

        return is_file($this->getPath($template));
    }

    public function load($template)
    {
        if ($template === '__compile_error_template') {
            return $this->getCompileErrorTemplate();
        }

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

    /**
     * @return string
     */
    private function getCompileErrorTemplate()
    {
        $closingTagPrefix = $this->environment->getOption('block_end_prefix', 'end');
        $source           = "{block error}<h1>Failed to compile {templateName}</h1>
<h2>Error message:</h2>
<p>{message}</p>
<h2>Template source:</h2>
<pre><code><ol start=\"{firstLine + 1}\">
{for lineNo: line in lines}
    <li>
    {if lineNo = errorLine}
        <b>{line}</b>
    {else}
        {line}
    {endif}
    </li>
{{$closingTagPrefix}for}
</ol></code></pre>{{$closingTagPrefix}block}";

        return strtr($source, array("\n" => ''));
    }
}
