<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty;

interface TemplateCacheInterface
{
    /**
     * Loads $file.
     *
     * @param $file
     */
    public function load($file);

    /**
     * Saves $compiled to $file.
     *
     * @param $file     string The file name in cache.
     * @param $compiled string The cache content.
     *
     * @return mixed
     */
    public function save($file, $compiled);

    /**
     * Returns whether $file exists in cache.
     *
     * @param $file
     *
     * @return bool
     */
    public function exists($file);

    /**
     * Returns the time $file was last saved to.
     *
     * @param $file
     *
     * @return int
     */
    public function getCreatedTime($file);
}
