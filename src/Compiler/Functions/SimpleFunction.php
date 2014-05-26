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
    private $functionName;

    public function __construct($name, $function = null, array $options = array())
    {
        parent::__construct($name, $options);
        $this->functionName = $function ? : $name;
    }

    public function getFunction()
    {
        return $this->functionName;
    }
}
