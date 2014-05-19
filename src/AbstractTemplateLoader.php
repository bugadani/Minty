<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating;

abstract class AbstractTemplateLoader
{
    abstract public function isCacheFresh($template);

    abstract public function exists($template);

    abstract public function load($template);

    abstract public function getTemplateClassName($template);

    abstract public function getCacheKey($template);
}
