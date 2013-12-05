<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Modules\Templating\Compiler\Functions;

use Modules\Templating\Compiler\TemplateFunction;

class MethodFunction extends TemplateFunction
{
    private $method_name;

    public function __construct($name, $method, $is_safe = false)
    {
        parent::__construct($name, $is_safe);
        $this->method_name = $method;
    }

    public function getMethod()
    {
        return $this->method_name;
    }
}
