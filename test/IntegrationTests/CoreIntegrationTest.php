<?php

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
        $env = new Environment($loader, [
            'fallback_tag'     => 'print',
            'global_variables' => ['global' => 'global variable']
        ]);
        $env->addFunction(
            new TemplateFunction('html_safe', function ($data) {
                    return $data;
                }, ['is_safe' => ['html', 'xml']]
            )
        );
        $env->addFunction(
            new TemplateFunction('json_safe', function ($data) {
                    return $data;
                }, ['is_safe' => 'json']
            )
        );
        $env->addFunction(
            new TemplateFunction('dump', function ($data) {
                return print_r($data, 1);
            }, ['is_safe' => true])
        );
        $env->addExtension(new Core());

        return $env;
    }
}
