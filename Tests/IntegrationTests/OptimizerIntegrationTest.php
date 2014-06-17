<?php
/**
 * Created by PhpStorm.
 * User: ASUS T100
 * Date: 2014.06.14.
 * Time: 21:46
 */

namespace Modules\Templating\IntegrationTests;

use Modules\Templating\Compiler\TemplateFunction;
use Modules\Templating\Environment;
use Modules\Templating\Extensions\Core;
use Modules\Templating\Extensions\Optimizer;
use Modules\Templating\TemplateLoaders\StringLoader;
use Modules\Templating\Test\IntegrationTestCase;

class OptimizerIntegrationTest extends IntegrationTestCase
{
    public function getTestDirectory()
    {
        return __DIR__ . '/fixtures';
    }

    public function getEnvironment(StringLoader $loader)
    {
        $env = new Environment($loader, array(
            'fallback_tag' => 'print'
        ));
        $env->addFunction(
            new TemplateFunction('html_safe', function ($data) {
                    return $data;
                }, array('is_safe' => array('html', 'xml'))
            )
        );
        $env->addFunction(
            new TemplateFunction('json_safe', function ($data) {
                    return $data;
                }, array('is_safe' => 'json')
            )
        );
        $env->addExtension(new Core());
        $env->addExtension(new Optimizer());

        return $env;
    }
}
