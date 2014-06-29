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
    public function __construct($message, $line = 0)
    {
        parent::__construct("{$message} found in line {$line}", $line);
    }
}
