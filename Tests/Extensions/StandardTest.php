<?php

namespace Modules\Templating\Extensions;

use Modules\Templating\Environment;
use Modules\Templating\TemplateLoaders\ChainLoader;

class StandardTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Environment
     */
    private $env;

    public function setUp()
    {
        $this->env = new Environment();
        $this->env->addExtension(
            new StandardFunctions()
        );
    }

    public function functionProvider()
    {
        return array(
            array('abs'),
            array('attributes'),
            array('batch'),
            array('capitalize'),
            array('cycle'),
            array('date_format'),
            array('extract'),
            array('first'),
            array('format'),
            array('is_int'),
            array('is_numeric'),
            array('is_string'),
            array('join'),
            array('json_encode'),
            array('keys'),
            array('last'),
            array('length'),
            array('link_to'),
            array('lower'),
            array('ltrim'),
            array('max'),
            array('merge'),
            array('min'),
            array('pluck'),
            array('random'),
            array('regexp_replace'),
            array('replace'),
            array('reverse'),
            array('rtrim'),
            array('shuffle'),
            array('slice'),
            array('sort'),
            array('source'),
            array('spacify'),
            array('split'),
            array('striptags'),
            array('title_case'),
            array('trim'),
            array('truncate'),
            array('upper'),
            array('url_encode'),
            array('without'),
            array('wordwrap')
        );
    }

    /**
     * @dataProvider functionProvider
     */
    public function testFunctionsAreAddedToEnvironment($function)
    {
        $this->assertTrue($this->env->hasFunction($function));
    }
}
