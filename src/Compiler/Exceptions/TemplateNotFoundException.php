<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Exceptions;

class TemplateNotFoundException extends \RuntimeException
{

    public function __construct($template)
    {
        parent::__construct("Template {$template} was not found");
    }
}
