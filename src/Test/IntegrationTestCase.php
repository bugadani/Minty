<?php

namespace Minty\Test;

use Minty\Environment;
use Minty\TemplateLoaders\StringLoader;

abstract class IntegrationTestCase extends \PHPUnit_Framework_TestCase
{
    private static $counter = 0;

    /**
     * @var StringLoader
     */
    private $stringLoader;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var \ReflectionProperty
     */
    private $optionsProperty;

    public function setUp()
    {
        $this->stringLoader = new StringLoader();
        $this->environment  = $this->getEnvironment($this->stringLoader);

        //this is needed to set options
        $reflection            = new \ReflectionClass($this->environment);
        $this->optionsProperty = $reflection->getProperty('options');
        $this->optionsProperty->setAccessible(true);
    }

    public function tearDown()
    {
        unset($this->environment);
        unset($this->stringLoader);
    }

    abstract public function getTestDirectory();

    /**
     * @param StringLoader $loader
     *
     * @return Environment
     */
    abstract public function getEnvironment(StringLoader $loader);

    public function getTests()
    {
        $directory = realpath($this->getTestDirectory());
        $tests     = array();

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($iterator as $file) {
            if (substr($file, -5) === '.test') {
                $tests[] = $this->parseDescriptor($directory, $file);
            }
        }

        return $tests;
    }

    private function getBlock($string, $block)
    {
        $matches = array();
        if (!preg_match("/^--{$block}--\n(.*?)\n(?:--(?:[A-Z]+)--|\\Z)/ms", $string, $matches)) {
            return false;
        }

        return $matches[1];
    }

    private function getTemplateBlocks($string)
    {
        $matches = array();
        $pattern = "/^--TEMPLATE(?:\\s*(.*?))?--\n(.*?)\n(?=--.+?--|\\Z)/ms";
        if (!preg_match_all($pattern, $string, $matches, PREG_SET_ORDER)) {
            return false;
        }

        $templates = array();
        foreach ($matches as $match) {
            if ($match[1] === '') {
                $match[1] = 'index';
            }
            $templates[$match[1]] = $match[2];
        }

        return $templates;
    }

    private function parseDescriptor($directory, $file)
    {
        $testDescriptor = file_get_contents($file);

        $file = str_replace($directory . DIRECTORY_SEPARATOR, '', $file);

        $testDescriptor = strtr(
            $testDescriptor,
            array(
                "\r\n" => "\n",
                "\n\r" => "\n"
            )
        );

        $test      = $this->getBlock($testDescriptor, 'TEST');
        $templates = $this->getTemplateBlocks($testDescriptor);
        $expect    = $this->getBlock($testDescriptor, 'EXPECT');
        $exception = $this->getBlock($testDescriptor, 'EXCEPTION');

        if (!$test) {
            throw new \RuntimeException("{$file} does not contain a TEST block");
        }
        if (!$templates) {
            throw new \RuntimeException("{$file} does not contain a TEMPLATE block");
        }
        if (!$expect && !$exception) {
            throw new \RuntimeException("{$file} does not contain a EXPECT or EXCEPTION block");
        }

        return array(
            $file,
            $test,
            $templates,
            $this->getBlock($testDescriptor, 'DATA'),
            $expect,
            $exception
        );
    }

    /**
     * @test
     * @dataProvider getTests
     */
    public function runIntegrationTests(
        $file,
        $description,
        $templates,
        $data,
        $expectation,
        $exception
    ) {
        //global counter to provide random namespaces to avoid class name collision
        $options = $this->optionsProperty->getValue($this->environment);

        $options['cache_namespace'] = 'test_' . ++self::$counter;

        $this->optionsProperty->setValue($this->environment, $options);
        foreach ($templates as $name => $template) {
            $this->stringLoader->addTemplate($name, $template);
        }

        if ($data) {
            eval('$data = ' . $data . ';');
        } else {
            $data = array();
        }

        try {
            ob_start();
            $this->environment->render('index', $data);
            $this->assertEquals(
                $expectation,
                rtrim(ob_get_clean(), "\n"),
                $description . ' (' . $file . ')'
            );
        } catch (\Exception $e) {
            if ($exception) {
                $this->assertInstanceOf($exception, $e);
            } else {
                throw $e;
            }
        }
    }
}
