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
        return [
            ['abs'],
            ['attributes'],
            ['batch'],
            ['capitalize'],
            ['cycle'],
            ['date_format'],
            ['extract'],
            ['first'],
            ['format'],
            ['is_int'],
            ['is_numeric'],
            ['is_string'],
            ['join'],
            ['json_encode'],
            ['keys'],
            ['last'],
            ['length'],
            ['link_to'],
            ['lower'],
            ['ltrim'],
            ['max'],
            ['merge'],
            ['min'],
            ['pluck'],
            ['random'],
            ['regexp_replace'],
            ['replace'],
            ['reverse'],
            ['rtrim'],
            ['shuffle'],
            ['slice'],
            ['sort'],
            ['source'],
            ['spacify'],
            ['split'],
            ['striptags'],
            ['title_case'],
            ['trim'],
            ['truncate'],
            ['upper'],
            ['url_encode'],
            ['without'],
            ['wordwrap']
        ];
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
        return [
            [
                'attributes',
                [['foo' => 'bar', 'baz' => 'foobar']],
                ' foo="bar" baz="foobar"'
            ],
            [
                'batch',
                [[1, 2, 3, 4], 2],
                [[0 => 1, 1 => 2], [2 => 3, 3 => 4]]
            ],
            [
                'batch',
                [[1, 2, 3, 4], 2, false],
                [[1, 2], [3, 4]]
            ],
            [
                'batch',
                [[1, 2, 3, 4], 3],
                [[1, 2, 3], [3 => 4]]
            ],
            [
                'batch',
                [[1, 2, 3, 4], 3, false, 'no item'],
                [[1, 2, 3], [4, 'no item', 'no item']]
            ],
            [
                'date_format',
                ['1990-08-09', 'd m Y'],
                '09 08 1990'
            ],
            [
                'first',
                ['foo'],
                'f'
            ],
            [
                'first',
                ['foo', 2],
                'fo'
            ],
            [
                'first',
                [['foo', 'bar']],
                ['foo']
            ],
            [
                'first',
                [['foo', 'bar'], 2],
                ['foo', 'bar']
            ],
            [
                'join',
                [['foo', 'bar'], 'glue'],
                'foogluebar'
            ],
            [
                'last',
                ['foo'],
                'o'
            ],
            [
                'last',
                ['foo', 2],
                'oo'
            ],
            [
                'last',
                [['foo', 'bar']],
                ['bar']
            ],
            [
                'last',
                [['foo', 'bar'], 2],
                ['foo', 'bar']
            ],
            [
                'length',
                ['foo'],
                3
            ],
            [
                'length',
                [['foo', 'bar']],
                2
            ],
            [
                'link_to',
                ['label', 'foourl'],
                '<a href="foourl">label</a>'
            ],
            [
                'link_to',
                ['label', 'foourl', ['class' => 'bar']],
                '<a class="bar" href="foourl">label</a>'
            ],
            [
                'pluck',
                [
                    [
                        ['a' => 'foo', 'b' => 'bar'],
                        ['a' => 'foobar', 'b' => 'baz']
                    ],
                    'a'
                ],
                ['foo', 'foobar']
            ],
            [
                'regexp_replace',
                ['string', '/in/', 'on'],
                'strong'
            ],
            [
                'replace',
                ['string', 'in', 'on'],
                'strong'
            ],
            [
                'reverse',
                ['string'],
                'gnirts'
            ],
            [
                'reverse',
                [[1, 2, 3]],
                [3, 2, 1]
            ],
            [
                'slice',
                ['string', 2, 3],
                'rin'
            ],
            [
                'slice',
                ['string', 2],
                'ring'
            ],
            [
                'slice',
                [[1, 2, 3], 1],
                [2, 3]
            ],
            [
                'slice',
                [[1, 2, 3], 1, 1],
                [2]
            ],
            [
                'slice',
                [[1, 2, 3], 1, 1, true],
                [1 => 2]
            ],
            [
                'sort',
                [[3, 1, 2]],
                [1, 2, 3]
            ],
            [
                'sort',
                [[3, 1, 2], true],
                [3, 2, 1]
            ],
            [
                'spacify',
                ['string'],
                's t r i n g'
            ],
            [
                'spacify',
                ['string', '.'],
                's.t.r.i.n.g'
            ],
            [
                'split',
                ['string'],
                ['s', 't', 'r', 'i', 'n', 'g']
            ],
            [
                'split',
                ['string', '', 2],
                ['st', 'ri', 'ng']
            ],
            [
                'split',
                ['s t r i n g', ' '],
                ['s', 't', 'r', 'i', 'n', 'g']
            ],
            [
                'split',
                ['s t r i n g', ' ', 2],
                ['s', 't']
            ],
            [
                'truncate',
                ['123456', 3],
                '123...'
            ],
            [
                'truncate',
                ['123456', 3, '\\'],
                '123\\'
            ],
            [
                'url_encode',
                [['foo' => 'bar', 'bar' => 'baz']],
                'foo=bar&bar=baz'
            ],
            [
                'url_encode',
                [' '],
                '%20'
            ],
            [
                'without',
                ['string', 'in'],
                'strg'
            ],
            [
                'without',
                ['string', ['in', 'st']],
                'rg'
            ],
            [
                'without',
                [[1, 2, 3], 3],
                [1, 2]
            ],
            [
                'without',
                [[1, 2, 3], [2, 3]],
                [1]
            ]
        ];
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
        $array = [1, 2, 3];
        $this->assertEquals(1, template_function_cycle($array));
        $this->assertEquals(2, template_function_cycle($array));
        $this->assertEquals(3, template_function_cycle($array));
        $this->assertEquals(1, template_function_cycle($array));
    }

    public function testExtract()
    {
        $array   = ['foo' => 'bar', 'bar' => 'baz'];
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
