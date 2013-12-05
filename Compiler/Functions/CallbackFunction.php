<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Modules\Templating\Compiler\Functions;

use Closure;
use InvalidArgumentException;
use Modules\Templating\Compiler\TemplateFunction;

class CallbackFunction extends TemplateFunction
{
    private $function;

    public function __construct($name, $function, $is_safe = false)
    {
        parent::__construct($name, $is_safe);
        if (!is_callable($function) && $function instanceof Closure) {
            throw new InvalidArgumentException('Filter must be a callable value');
        }
        $this->function = $function;
    }

    public function callFunction(array $arguments)
    {
        return call_user_func_array($this->function, $arguments);
    }
}
