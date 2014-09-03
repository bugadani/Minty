<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Exceptions;

class SyntaxException extends TemplatingException
{
    public function __construct($message, $line = 0, $previous = null)
    {
        parent::__construct("{$message} in line {$line}", $line, $previous);
    }
}
