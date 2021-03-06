<?php

namespace Minty\Test;

use Minty\Environment;
use Minty\TemplateLoaders\StringLoader;

abstract class IntegrationTestCase extends \PHPUnit_Framework_TestCase
{
    const TEST_FOR_RESULT = 1;
    const TEST_FOR_EXCEPTION = 2;

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

    protected function runTestsFor()
    {
        return self::TEST_FOR_RESULT | self::TEST_FOR_EXCEPTION;
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

        $iterator = new \CallbackFilterIterator(
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory),
                \RecursiveIteratorIterator::LEAVES_ONLY
            ),
            function (\SplFileInfo $file) {
                return $file->getExtension() === 'test';
            }
        );

        $tests = [];
        foreach ($iterator as $file) {
            $tests[] = $this->parseDescriptor($directory, $file);
        }

        return array_filter($tests);
    }

    private function getBlock($string, $block)
    {
        $matches = [];
        if (!preg_match("/^--{$block}--\n(.*?)(?:\n--(?:[A-Z]+)--|\\Z)/ms", $string, $matches)) {
            return false;
        }

        return $matches[1];
    }

    private function getTemplateBlocks($string)
    {
        $matches = [];
        $pattern = "/^--TEMPLATE(?:\\s*(.*?))?--\n(.*?)\n(?=--.+?--|\\Z)/ms";
        if (!preg_match_all($pattern, $string, $matches, PREG_SET_ORDER)) {
            return false;
        }

        $templates = [];
        foreach ($matches as $match) {
            if ($match[1] === '') {
                $match[1] = 'index';
            }
            $templates[ $match[1] ] = $match[2];
        }

        return $templates;
    }

    private function parseDescriptor($directory, $file)
    {
        $testDescriptor = file_get_contents($file);

        $file = str_replace($directory . DIRECTORY_SEPARATOR, '', $file);

        $testDescriptor = strtr(
            $testDescriptor,
            [
                "\r\n" => "\n",
                "\n\r" => "\n"
            ]
        );

        $test      = $this->getBlock($testDescriptor, 'TEST');
        $templates = $this->getTemplateBlocks($testDescriptor);
        $expect    = $this->getBlock($testDescriptor, 'EXPECT');
        $exception = $this->getBlock($testDescriptor, 'EXCEPTION');

        $exceptionMessage = null;

        if (!$test) {
            throw new \RuntimeException("{$file} does not contain a TEST block");
        }
        if (!$templates) {
            throw new \RuntimeException("{$file} does not contain a TEMPLATE block");
        }
        if ($expect === false && $exception === false) {
            throw new \RuntimeException("{$file} does not contain a EXPECT or EXCEPTION block");
        }

        $testFor = $this->runTestsFor();
        if ($expect && (($testFor & self::TEST_FOR_RESULT) === 0)) {
            return false;
        }

        if ($exception) {
            if (($testFor & self::TEST_FOR_EXCEPTION) === 0) {
                return false;
            }

            if (strpos($exception, "\n")) {
                list($exception, $exceptionMessage) = explode("\n", $exception, 2);
            }
        }

        return [
            $file,
            $test,
            $templates,
            $this->getBlock($testDescriptor, 'DATA'),
            $expect,
            $exception,
            $exceptionMessage
        ];
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
        $exception,
        $exceptionMessage
    ) {
        //global counter to provide random namespaces to avoid class name collision
        $options                    = $this->optionsProperty->getValue($this->environment);
        $options['cache_namespace'] = 'test_' . ++self::$counter;
        $this->optionsProperty->setValue($this->environment, $options);

        foreach ($templates as $name => $template) {
            $this->stringLoader->addTemplate($name, $template);
        }

        if ($data) {
            eval('$data = ' . $data . ';');
        } else {
            $data = [];
        }

        if ($exception) {
            $this->setExpectedException($exception, $exceptionMessage);
        }

        $obLevel = ob_get_level();
        try {
            ob_start();
            $this->environment->render('index', $data);
            $output = rtrim(ob_get_clean(), "\n");
            if ($expectation) {
                $this->assertEquals(
                    $expectation,
                    $output,
                    $description . ' (' . $file . ')'
                );
            }
        } catch (\Exception $e) {
            if (ob_get_level() !== $obLevel) {
                ob_end_clean();
            }
            throw $e;
        }
    }
}
