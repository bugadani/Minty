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

    public function __construct($name, $method, array $options = array())
    {
        parent::__construct($name, $options);
        $this->method_name = $method;
    }

    public function getMethod()
    {
        return $this->method_name;
    }
}
