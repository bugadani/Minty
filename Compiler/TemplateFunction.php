<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Modules\Templating\Compiler;

use Closure;
use InvalidArgumentException;

abstract class TemplateFunction
{
    /**
     * @var bool
     */
    private $is_safe;
    private $name;
    private $extension;

    /**
     * @param Closure|callback $filter
     * @param bool $is_safe
     * @throws InvalidArgumentException
     */
    public function __construct($name, $is_safe = false)
    {
        $this->is_safe = $is_safe;
        $this->name    = $name;
    }

    public function setExtensionName($extension)
    {
        $this->extension = $extension;
    }

    public function getExtensionName()
    {
        return $this->extension;
    }

    /**
     * @return bool
     */
    public function isSafe()
    {
        return $this->is_safe;
    }

    public function getFunctionName()
    {
        return $this->name;
    }
}
