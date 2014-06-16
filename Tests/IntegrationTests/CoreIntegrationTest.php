<?php
/**
 * Created by PhpStorm.
 * User: ASUS T100
 * Date: 2014.06.14.
 * Time: 21:46
 */

namespace Modules\Templating\IntegrationTests;

use Modules\Templating\Environment;
use Modules\Templating\Extensions\Core;
use Modules\Templating\TemplateLoaders\ChainLoader;
use Modules\Templating\TemplateLoaders\StringLoader;
use Modules\Templating\Test\IntegrationTestCase;

class CoreIntegrationTest extends IntegrationTestCase
{
    public function getTestDirectory()
    {
        return __DIR__ . '/fixtures';
    }

    public function getEnvironment()
    {
        $env = new Environment(array(
            'fallback_tag' => 'print'
        ));
        $env->addExtension(new Core());

        return $env;
    }
}
