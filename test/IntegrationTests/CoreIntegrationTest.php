<?php
/**
 * Created by PhpStorm.
 * User: ASUS T100
 * Date: 2014.06.14.
 * Time: 21:46
 */

namespace Minty\IntegrationTests;

use Minty\Compiler\TemplateFunction;
use Minty\Environment;
use Minty\Extensions\Core;
use Minty\TemplateLoaders\StringLoader;
use Minty\Test\IntegrationTestCase;

class CoreIntegrationTest extends IntegrationTestCase
{
    public function getTestDirectory()
    {
        return __DIR__ . '/fixtures';
    }

    protected function runTestsFor()
    {
        return self::TEST_FOR_RESULT;
    }

    public function getEnvironment(StringLoader $loader)
    {
        $env = new Environment($loader, array(
            'fallback_tag'     => 'print',
            'global_variables' => array('global' => 'global variable')
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
        $env->addFunction(
            new TemplateFunction('dump', function ($data) {
                return print_r($data, 1);
            }, array('is_safe' => true))
        );
        $env->addExtension(new Core());

        return $env;
    }
}
