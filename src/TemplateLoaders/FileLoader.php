<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\TemplateLoaders;

use Minty\Environment;
use Minty\EnvironmentAwareInterface;
use Minty\TemplateLoaderInterface;

class FileLoader implements TemplateLoaderInterface, EnvironmentAwareInterface
{
    /**
     * @var Environment
     */
    private $environment;
    private $root;
    private $extension;

    public function __construct($root, $extension)
    {
        $this->root      = realpath($root);
        $this->extension = $extension;
    }

    /**
     * @inheritdoc
     */
    public function setEnvironment(Environment $environment)
    {
        $this->environment = $environment;
    }

    private function getPath($template)
    {
        return "{$this->root}/{$template}.{$this->extension}";
    }

    /**
     * @inheritdoc
     */
    public function isCacheFresh($template)
    {
        $cachePath = $this->environment->getCachePath(
            $this->getCacheKey($template)
        );

        if (!is_file($cachePath)) {
            return false;
        }

        return filemtime($this->getPath($template)) < filemtime($cachePath);
    }

    /**
     * @inheritdoc
     */
    public function exists($template)
    {
        return is_file($this->getPath($template));
    }

    /**
     * @inheritdoc
     */
    public function load($template)
    {
        return file_get_contents($this->getPath($template));
    }

    /**
     * @inheritdoc
     */
    public function getCacheKey($template)
    {
        return dirname($template) . '/template_' . basename($template);
    }
}
