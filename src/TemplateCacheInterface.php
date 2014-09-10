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
    public function load($file);

    public function save($file, $compiled);

    public function exists($file);

    public function getCreatedTime($file);
}
