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

class StringLoader implements TemplateLoaderInterface, EnvironmentAwareInterface
{
    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var string[]
     */
    private $templates = [];

    /**
     * @inheritdoc
     */
    public function setEnvironment(Environment $environment)
    {
        $this->environment = $environment;
    }

    public function addTemplate($name, $template)
    {
        $this->templates[$name] = $template;
    }

    /**
     * @inheritdoc
     */
    public function isCacheFresh($template)
    {
        return $this->environment
            ->getTemplateCache()
            ->exists($this->getCacheKey($template));
    }

    /**
     * @inheritdoc
     */
    public function exists($template)
    {
        return isset($this->templates[$template]);
    }

    /**
     * @inheritdoc
     */
    public function load($template)
    {
        return $this->templates[$template];
    }

    /**
     * @inheritdoc
     */
    public function getCacheKey($template)
    {
        //embedded templates are not present in the loader so they can't have a hashed suffix
        //they also don't need one because they get recompiled with the template that uses them
        if (isset($this->templates[$template])) {
            $template = $template . '_' . md5($this->templates[$template]);
        }

        return $template;
    }
}
