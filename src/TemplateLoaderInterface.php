<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty;

interface TemplateLoaderInterface
{
    public function isCacheFresh($template);

    public function exists($template);

    public function load($template);

    public function getCacheKey($template);
}
