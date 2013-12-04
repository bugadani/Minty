<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating;

use Modules\Templating\Compiler\Token;
use PHPUnit_Framework_TestCase;

require_once __DIR__ . '/../../Compiler/Token.php';

class TokenTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Token
     */
    protected $object;

    protected function setUp()
    {
        $this->object = new Token(Token::TEXT, 'some text', 3);
    }

    public function testTest()
    {
        $this->assertTrue($this->object->test(Token::TEXT));
        $this->assertTrue($this->object->test(Token::TEXT, 'some text'));
        $this->assertTrue($this->object->test(Token::TEXT, 'some text', 3));
        $this->assertTrue($this->object->test(Token::TEXT, 'is_string', 3));

        $this->assertFalse($this->object->test(Token::EOF));
        $this->assertFalse($this->object->test(Token::TEXT, 'sometext'));
        $this->assertFalse($this->object->test(Token::TEXT, 'some text', 2));
        $this->assertFalse($this->object->test(Token::TEXT, 'is_numeric'));
    }

    public function testGetType()
    {
        $this->assertEquals(Token::TEXT, $this->object->getType());
    }

    public function testGetValue()
    {
        $this->assertEquals('some text', $this->object->getValue());
    }

    /**
     * @covers Modules\Templating\Token::getLine
     * @todo   Implement testGetLine().
     */
    public function testGetLine()
    {
        $this->assertEquals(3, $this->object->getLine());
    }

    /**
     * @covers Modules\Templating\Token::getTypeString
     * @todo   Implement testGetTypeString().
     */
    public function testGetTypeString()
    {
        $this->assertEquals('TEXT', $this->object->getTypeString());
    }
}
