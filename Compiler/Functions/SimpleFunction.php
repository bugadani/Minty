<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Modules\Templating\Compiler\Functions;

use Modules\Templating\Compiler\TemplateFunction;

class SimpleFunction extends TemplateFunction
{
    private $function_name;

    function __construct($name, $function = null, $is_safe = false)
    {
        parent::__construct($name, $is_safe);

        if ($function === null) {
            $function = $name;
        }

        $this->function_name = $function;
    }

    public function getFunction()
    {
        return $this->function_name;
    }
}
