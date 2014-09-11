<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\TemplateLoaders;

use Minty\Compiler\Exceptions\TemplateNotFoundException;
use Minty\TemplateLoaderInterface;

class ChainLoader implements TemplateLoaderInterface
{
    /**
     * @var TemplateLoaderInterface[]
     */
    private $loaders = [];

    /**
     * @var TemplateLoaderInterface[]
     */
    private $templateMap = [];

    public function __construct()
    {
        foreach (func_get_args() as $loader) {
            $this->addLoader($loader);
        }
    }

    /**
     * @param TemplateLoaderInterface $loader
     */
    public function addLoader(TemplateLoaderInterface $loader)
    {
        $this->loaders[] = $loader;
    }

    /**
     * @inheritdoc
     */
    public function isCacheFresh($template)
    {
        if (!$this->exists($template)) {
            throw new TemplateNotFoundException($template);
        }

        return $this->templateMap[$template]->isCacheFresh($template);
    }

    /**
     * @inheritdoc
     */
    public function exists($template)
    {
        if (isset($this->templateMap[$template])) {
            return true;
        }

        foreach ($this->loaders as $loader) {
            if ($loader->exists($template)) {
                $this->templateMap[$template] = $loader;

                return true;
            }
        }

        return false;
    }

    /**
     * @inheritdoc
     */
    public function load($template)
    {
        if (!$this->exists($template)) {
            throw new TemplateNotFoundException($template);
        }

        return $this->templateMap[$template]->load($template);
    }

    /**
     * @inheritdoc
     */
    public function getCacheKey($template)
    {
        if (!$this->exists($template)) {
            throw new TemplateNotFoundException($template);
        }

        return $this->templateMap[$template]->getCacheKey($template);
    }
}
