<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty;

abstract class AbstractTemplateLoader
{
    abstract public function isCacheFresh($template);

    abstract public function exists($template);

    abstract public function load($template);

    abstract public function getCacheKey($template);
}
