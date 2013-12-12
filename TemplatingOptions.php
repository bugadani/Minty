<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating;

use RuntimeException;

class TemplatingOptions
{
    public $template_path     = 'templates/%s.tpl';
    public $cache_path        = 'templates/compiled/%s.php';
    public $cache_namespace   = 'Application\\Templating\\Cached';
    public $reload            = false;
    public $strict_mode       = true;
    public $autoescape        = true;
    public $global_variables  = array();

    public function __construct(array $options = array())
    {
        foreach ($options as $key => $option) {
            $this->$key = $option;
        }
    }

    public function __set($key, $value)
    {
        // nop
    }

    public function __get($key)
    {
        throw new RuntimeException('Property not found: ' . $key);
    }
}
