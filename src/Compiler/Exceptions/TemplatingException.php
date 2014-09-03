<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Exceptions;

class TemplatingException extends \RuntimeException
{
    private $sourceLine;

    public function __construct($message, $sourceLine = 0, $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->sourceLine = $sourceLine;
    }

    public function getSourceLine()
    {
        return $this->sourceLine;
    }
}
