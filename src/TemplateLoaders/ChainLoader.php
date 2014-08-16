<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\TemplateLoaders;

use Minty\AbstractTemplateLoader;
use Minty\Compiler\Exceptions\TemplateNotFoundException;

class ChainLoader extends AbstractTemplateLoader
{
    /**
     * @var AbstractTemplateLoader[]
     */
    private $loaders = [];

    /**
     * @var AbstractTemplateLoader[]
     */
    private $templateMap = [];

    public function addLoader(AbstractTemplateLoader $loader)
    {
        $this->loaders[] = $loader;
    }

    public function isCacheFresh($template)
    {
        if (!$this->exists($template)) {
            throw new TemplateNotFoundException($template);
        }

        return $this->templateMap[$template]->isCacheFresh($template);
    }

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

    public function load($template)
    {
        if (!$this->exists($template)) {
            throw new TemplateNotFoundException($template);
        }

        return $this->templateMap[$template]->load($template);
    }

    public function getCacheKey($template)
    {
        if (!$this->exists($template)) {
            throw new TemplateNotFoundException($template);
        }

        return $this->templateMap[$template]->getCacheKey($template);
    }
}
