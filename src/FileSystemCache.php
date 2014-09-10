<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty;

use Minty\TemplateCacheInterface;

class FileSystemCache implements TemplateCacheInterface
{
    private $directory;

    public function __construct($cacheDir)
    {
        $this->directory = $cacheDir;
    }

    public function load($file)
    {
        includeFile($this->getCachePath($file));
    }

    public function save($file, $compiled)
    {
        $destination = $this->getCachePath($file);

        $cacheDir = dirname($destination);
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0777, true);
        }
        file_put_contents($destination, $compiled);
    }

    public function getCreatedTime($file)
    {
        $cachePath = $this->getCachePath($file);

        if (!is_file($cachePath)) {
            return 0;
        }

        return filemtime($cachePath);
    }

    public function exists($file)
    {
        return is_file($this->getCachePath($file));
    }

    private function getCachePath($file)
    {
        return $this->directory . '/' . $file . '.php';
    }
}

function includeFile($file)
{
    include $file;
}
