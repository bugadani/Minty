<?php

namespace Minty\Extensions;

use Minty\Context;
use Minty\Environment;
use Minty\TemplateLoaders\StringLoader;

class CoreFunctionsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Environment
     */
    private $env;

    /**
     * @var StringLoader
     */
    private $stringLoader;

    public function setUp()
    {
        $this->stringLoader = new StringLoader();
        $this->env          = new Environment($this->stringLoader);
        $this->env->addExtension(
            new Core()
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

    public function returnValueProvider()
    {
        return array(
            array(
                'attributes',
                array(array('foo' => 'bar', 'baz' => 'foobar')),
                ' foo="bar" baz="foobar"'
            ),
            array(
                'batch',
                array(array(1, 2, 3, 4), 2),
                array(array(0 => 1, 1 => 2), array(2 => 3, 3 => 4))
            ),
            array(
                'batch',
                array(array(1, 2, 3, 4), 2, false),
                array(array(1, 2), array(3, 4))
            ),
            array(
                'batch',
                array(array(1, 2, 3, 4), 3),
                array(array(1, 2, 3), array(3 => 4))
            ),
            array(
                'batch',
                array(array(1, 2, 3, 4), 3, false, 'no item'),
                array(array(1, 2, 3), array(4, 'no item', 'no item'))
            ),
            array(
                'date_format',
                array('1990-08-09', 'd m Y'),
                '09 08 1990'
            ),
            array(
                'first',
                array('foo'),
                'f'
            ),
            array(
                'first',
                array('foo', 2),
                'fo'
            ),
            array(
                'first',
                array(array('foo', 'bar')),
                array('foo')
            ),
            array(
                'first',
                array(array('foo', 'bar'), 2),
                array('foo', 'bar')
            ),
            array(
                'join',
                array(array('foo', 'bar'), 'glue'),
                'foogluebar'
            ),
            array(
                'last',
                array('foo'),
                'o'
            ),
            array(
                'last',
                array('foo', 2),
                'oo'
            ),
            array(
                'last',
                array(array('foo', 'bar')),
                array('bar')
            ),
            array(
                'last',
                array(array('foo', 'bar'), 2),
                array('foo', 'bar')
            ),
            array(
                'length',
                array('foo'),
                3
            ),
            array(
                'length',
                array(array('foo', 'bar')),
                2
            ),
            array(
                'link_to',
                array('label', 'foourl'),
                '<a href="foourl">label</a>'
            ),
            array(
                'link_to',
                array('label', 'foourl', array('class' => 'bar')),
                '<a class="bar" href="foourl">label</a>'
            ),
            array(
                'pluck',
                array(
                    array(
                        array('a' => 'foo', 'b' => 'bar'),
                        array('a' => 'foobar', 'b' => 'baz')
                    ),
                    'a'
                ),
                array('foo', 'foobar')
            ),
            array(
                'regexp_replace',
                array('string', '/in/', 'on'),
                'strong'
            ),
            array(
                'replace',
                array('string', 'in', 'on'),
                'strong'
            ),
            array(
                'reverse',
                array('string'),
                'gnirts'
            ),
            array(
                'reverse',
                array(array(1, 2, 3)),
                array(3, 2, 1)
            ),
            array(
                'slice',
                array('string', 2, 3),
                'rin'
            ),
            array(
                'slice',
                array('string', 2),
                'ring'
            ),
            array(
                'slice',
                array(array(1, 2, 3), 1),
                array(2, 3)
            ),
            array(
                'slice',
                array(array(1, 2, 3), 1, 1),
                array(2)
            ),
            array(
                'slice',
                array(array(1, 2, 3), 1, 1, true),
                array(1 => 2)
            ),
            array(
                'sort',
                array(array(3, 1, 2)),
                array(1, 2, 3)
            ),
            array(
                'sort',
                array(array(3, 1, 2), true),
                array(3, 2, 1)
            ),
            array(
                'spacify',
                array('string'),
                's t r i n g'
            ),
            array(
                'spacify',
                array('string', '.'),
                's.t.r.i.n.g'
            ),
            array(
                'split',
                array('string'),
                array('s', 't', 'r', 'i', 'n', 'g')
            ),
            array(
                'split',
                array('string', '', 2),
                array('st', 'ri', 'ng')
            ),
            array(
                'split',
                array('s t r i n g', ' '),
                array('s', 't', 'r', 'i', 'n', 'g')
            ),
            array(
                'split',
                array('s t r i n g', ' ', 2),
                array('s', 't')
            ),
            array(
                'truncate',
                array('123456', 3),
                '123...'
            ),
            array(
                'truncate',
                array('123456', 3, '\\'),
                '123\\'
            ),
            array(
                'url_encode',
                array(array('foo' => 'bar', 'bar' => 'baz')),
                'foo=bar&bar=baz'
            ),
            array(
                'url_encode',
                array(' '),
                '+'
            ),
            array(
                'url_encode',
                array(' ', true),
                '%20'
            ),
            array(
                'without',
                array('string', 'in'),
                'strg'
            ),
            array(
                'without',
                array('string', array('in', 'st')),
                'rg'
            ),
            array(
                'without',
                array(array(1, 2, 3), 3),
                array(1, 2)
            ),
            array(
                'without',
                array(array(1, 2, 3), array(2, 3)),
                array(1)
            )
        );
    }

    /**
     * @dataProvider returnValueProvider
     */
    public function testFunctionReturnValues($function, $arguments, $expected)
    {
        $actual = call_user_func_array(
            $this->env->getFunction($function)->getCallback(),
            $arguments
        );
        $this->assertEquals($expected, $actual);
    }

    public function testCycle()
    {
        $array = array(1, 2, 3);
        $this->assertEquals(1, template_function_cycle($array));
        $this->assertEquals(2, template_function_cycle($array));
        $this->assertEquals(3, template_function_cycle($array));
        $this->assertEquals(1, template_function_cycle($array));
    }

    public function testExtract()
    {
        $array   = array('foo' => 'bar', 'bar' => 'baz');
        $context = new Context($this->env);
        template_function_extract($context, $array, 'foo');

        $this->assertEquals('bar', $context->foo);
        $this->assertFalse(isset($context->bar));
    }

    public function testSource()
    {
        $loader = $this->stringLoader;
        $loader->addTemplate('foo', 'bar');

        $this->assertEquals('bar', template_function_source($this->env, 'foo'));
    }
}
