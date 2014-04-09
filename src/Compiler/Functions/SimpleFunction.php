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

    public function __construct($name, $function = null, array $options = array())
    {
        parent::__construct($name, $options);
        $this->function_name = $function ? : $name;
    }

    public function getFunction()
    {
        return $this->function_name;
    }
}
