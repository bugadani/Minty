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

class StringLoader extends AbstractTemplateLoader
{
    /**
     * @var Environment
     */
    private $environment;
    private $templates = array();

    public function __construct(Environment $environment)
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

        return is_file($cachePath) && !$this->environment->getOption('debug', false);
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
        return $template .'_'. md5($this->templates[$template]);
    }
}
