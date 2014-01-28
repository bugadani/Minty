<?php

spl_autoload_register(function($class) {
    $file = str_replace('Modules\Templating', '../', $class) . '.php';
    if (is_file($file)) {
        require_once $file;
    }
});
