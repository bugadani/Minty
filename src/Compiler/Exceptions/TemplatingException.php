<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Exceptions;

class TemplatingException extends \RuntimeException
{
    private $sourceLine;

    public function __construct($message, $sourceLine = 0)
    {
        parent::__construct("{$message} found in line {$sourceLine}");
        $this->sourceLine = $sourceLine;
    }

    public function getSourceLine()
    {
        return $this->sourceLine;
    }
}
