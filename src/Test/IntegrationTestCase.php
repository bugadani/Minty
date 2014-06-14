<?php

namespace Modules\Templating\Test;

use Modules\Templating\TemplateLoader;
use Modules\Templating\TemplateLoaders\StringLoader;

abstract class IntegrationTestCase extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TemplateLoader
     */
    private $loader;

    /**
     * @var StringLoader
     */
    private $stringLoader;

    public function setUp()
    {
        $environment        = $this->getEnvironment();
        $this->stringLoader = new StringLoader($environment);
        $this->loader       = new TemplateLoader($environment, $this->stringLoader);
    }

    abstract public function getTestDirectory();

    abstract public function getEnvironment();

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
        if (!preg_match("/^--{$block}--\n(.*?)\n(?:--(?:[A-Z]+)--|$)/m", $string, $matches)) {
            return false;
        }

        return $matches[1];
    }

    private function getTemplateBlocks($string)
    {
        $matches = array();
        if (!preg_match_all(
            "/^--TEMPLATE( [a-z0-9]*)?--\n(.*?)\n(?:--(?:[A-Z]+)--|$)/m",
            $string,
            $matches,
            PREG_SET_ORDER
        )
        ) {
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

        $test      = $this->getBlock($testDescriptor, 'TEST');
        $templates = $this->getTemplateBlocks($testDescriptor);
        $expect    = $this->getBlock($testDescriptor, 'EXPECT');
        if (!$test) {
            throw new \RuntimeException("{$file} does not contain a TEST block");
        }
        if (!$templates) {
            throw new \RuntimeException("{$file} does not contain a TEMPLATE block");
        }
        if (!$expect) {
            throw new \RuntimeException("{$file} does not contain a EXPECT block");
        }

        return array(
            str_replace($directory . DIRECTORY_SEPARATOR, '', $file),
            $test,
            $templates,
            $this->getBlock($testDescriptor, 'DATA'),
            $expect
        );
    }

    /**
     * @test
     * @dataProvider getTests
     */
    public function runIntegrationTests($file, $description, $templates, $data, $expectation)
    {
        foreach ($templates as $name => $template) {
            $this->stringLoader->addTemplate($name, $template);
        }

        if ($data) {
            eval('$data = ' . $data . ';');
        } else {
            $data = array();
        }

        ob_start();
        $this->loader->render('index', $data);
        $this->assertEquals($expectation, ob_get_clean(), $description . ' (' . $file . ')');
    }
}
