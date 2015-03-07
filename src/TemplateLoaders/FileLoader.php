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
        $this->root = realpath($root);
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
        $cacheTime = $this->environment
            ->getTemplateCache()
            ->getCreatedTime($this->getCacheKey($template));

        return filemtime($this->getPath($template)) < $cacheTime;
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
        $dirname = dirname($template);
        if ($dirname == '.') {
            return 'template_' . basename($template);
        } else {
            return $dirname . '/template_' . basename($template);
        }
    }
}
