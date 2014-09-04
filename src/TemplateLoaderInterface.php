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
     * @param $template
     *
     * @return bool
     */
    public function isCacheFresh($template);

    /**
     * @param $template
     *
     * @return bool
     */
    public function exists($template);

    /**
     * @param $template
     *
     * @return string
     */
    public function load($template);

    /**
     * @param $template
     *
     * @return string
     */
    public function getCacheKey($template);
}
