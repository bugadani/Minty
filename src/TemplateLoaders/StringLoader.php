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
    private $templates = [];

    public function setEnvironment(Environment $environment)
    {
        $this->environment = $environment;
    }

    public function addTemplate($name, $template)
    {
        $this->templates[$name] = $template;
    }

    public function isCacheFresh($template)
    {
        $cachePath = $this->environment->getCachePath(
            $this->getCacheKey($template)
        );

        return is_file($cachePath);
    }

    public function exists($template)
    {
        return isset($this->templates[$template]);
    }

    public function load($template)
    {
        return $this->templates[$template];
    }

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
