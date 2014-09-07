<?php

namespace Minty\IntegrationTests;

use Minty\Environment;
use Minty\Extensions\Core;
use Minty\TemplateLoaders\StringLoader;
use Minty\Test\IntegrationTestCase;

class ExceptionIntegrationTest extends IntegrationTestCase
{
    public function getTestDirectory()
    {
        return __DIR__ . '/fixtures';
    }

    protected function runTestsFor()
    {
        return self::TEST_FOR_EXCEPTION;
    }

    public function getEnvironment(StringLoader $loader)
    {
        $env = new Environment($loader, [
            'fallback_tag'   => 'print',
            'error_template' => false
        ]);
        $env->addExtension(new Core());

        return $env;
    }
}
