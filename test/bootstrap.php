<?php

date_default_timezone_set('UTC');
if (is_file('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
} elseif (is_file('../vendor/autoload.php')) {
    require_once '../vendor/autoload.php';
}
