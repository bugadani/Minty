<?php

namespace Minty\IntegrationTests;

use Minty\Compiler\Exceptions\TemplatingException;
use Minty\Environment;
use Minty\Extensions\Core;
use Minty\TemplateLoaders\StringLoader;

class CustomDelimitersTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Environment
     */
    private $env;

    /**
     * @var StringLoader
     */
    private $loader;

    public function setUp()
    {
        $this->loader = new StringLoader();
        $this->env    = new Environment($this->loader, [
            'delimiters' => [
                'tag' => ['{*', '*}']
            ]
        ]);
        $this->env->addExtension(new Core());
    }

    public function tearDown()
    {
        unset($this->env);
    }

    public function testThatDefaultErrorTemplateRespectsCustomDelimiters()
    {
        $this->loader->addTemplate('template', '{*');

        ob_start();
        try {
            $this->env->render('template');
        } catch (TemplatingException $e) {
            $this->fail();
        }
        ob_end_clean();
    }
}
