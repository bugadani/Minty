<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Exceptions;

class TemplateNotFoundException extends \RuntimeException
{

    public function __construct($template)
    {
        parent::__construct("Template {$template} was not found");
    }
}
