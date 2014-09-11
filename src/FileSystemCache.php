<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty;

class FileSystemCache implements TemplateCacheInterface
{
    private $directory;

    /**
     * @param $cacheDir string The directory where the compiled templates will be saved.
     */
    public function __construct($cacheDir)
    {
        $this->directory = $cacheDir;
    }

    /**
     * @inheritdoc
     */
    public function load($file)
    {
        includeFile($this->getCachePath($file));
    }

    /**
     * @inheritdoc
     */
    public function save($file, $compiled)
    {
        $destination = $this->getCachePath($file);

        $cacheDir = dirname($destination);
        if (!is_dir($cacheDir)) {
            if (!mkdir($cacheDir, 0777, true)) {
                throw new \UnexpectedValueException("Could not create {$cacheDir} directory.");
            }
        }
        file_put_contents($destination, $compiled);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedTime($file)
    {
        $cachePath = $this->getCachePath($file);

        if (!is_file($cachePath)) {
            return 0;
        }

        return filemtime($cachePath);
    }

    /**
     * @inheritdoc
     */
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
