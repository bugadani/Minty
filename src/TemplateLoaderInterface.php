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
    /**
     * Returns whether the cached template is up to date.
     * @param $template
     *
     * @return bool
     */
    public function isCacheFresh($template);

    /**
     * Returns whether $template exists.
     * @param $template
     *
     * @return bool
     */
    public function exists($template);

    /**
     * Loads the contents of $template.
     * @param $template
     *
     * @return string
     */
    public function load($template);

    /**
     * Returns with a unique cache key for $template.
     * @param $template
     *
     * @return string
     */
    public function getCacheKey($template);
}
