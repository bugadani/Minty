<?php

namespace Minty\Compiler;

class TokenTest extends \PHPUnit_Framework_TestCase
{

    public function testGetters()
    {
        $token = new Token(Token::IDENTIFIER, 'foobar', 7);

        $this->assertNotEquals(Token::TEXT, $token->getType());
        $this->assertEquals(Token::IDENTIFIER, $token->getType());

        $this->assertNotEquals('foo', $token->getValue());
        $this->assertEquals('foobar', $token->getValue());

        $this->assertNotEquals(6, $token->getLine());
        $this->assertEquals(7, $token->getLine());
    }

    public function testSimpleTokenWithoutValue()
    {
        $token = new Token(Token::TAG_START);
        $this->assertFalse($token->test(Token::TEXT));

        $this->assertTrue($token->test(Token::TAG_START));
        $this->assertFalse($token->test(Token::TAG_START, 4));
    }

    public function testSimpleTokenWithValue()
    {
        $token = new Token(Token::LITERAL, 4);

        $this->assertFalse($token->test(Token::TEXT));
        $this->assertFalse($token->test(Token::LITERAL, 3));
        $this->assertFalse($token->test(Token::LITERAL, [3, 5]));
        $this->assertFalse($token->test(Token::LITERAL, 'is_string'));

        $this->assertTrue($token->test(Token::LITERAL));
        $this->assertTrue($token->test(Token::LITERAL, 4));
        $this->assertTrue($token->test(Token::LITERAL, 'is_int'));
        $this->assertTrue($token->test(Token::LITERAL, [4, 5]));
    }
}
