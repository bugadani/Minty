<?php

namespace Modules\Templating\Compiler;

class TokenTest extends \PHPUnit_Framework_TestCase
{

    public function tokenProvider()
    {
        return array(
            array(Token::EXPRESSION_START, 'EXPRESSION START'),
            array(Token::EXPRESSION_END, 'EXPRESSION END'),
            array(Token::LITERAL, 'LITERAL'),
            array(Token::STRING, 'STRING'),
            array(Token::IDENTIFIER, 'IDENTIFIER'),
            array(Token::OPERATOR, 'OPERATOR'),
            array(Token::PUNCTUATION, 'PUNCTUATION'),
            array(Token::TEXT, 'TEXT'),
            array(Token::TAG, 'TAG'),
            array(Token::EOF, 'EOF'),
            array(152, 'UNKNOWN 152'),
        );
    }

    /**
     * @dataProvider tokenProvider
     */
    public function testTypeStrings($type, $string)
    {
        $token = new Token($type);
        $this->assertEquals($string, $token->getTypeString());
    }

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
        $token = new Token(Token::EXPRESSION_START);
        $this->assertFalse($token->test(Token::TEXT));

        $this->assertTrue($token->test(Token::EXPRESSION_START));
        $this->assertFalse($token->test(Token::EXPRESSION_START, 4));
    }

    public function testSimpleTokenWithValue()
    {
        $token = new Token(Token::LITERAL, 4);

        $this->assertFalse($token->test(Token::TEXT));
        $this->assertFalse($token->test(Token::LITERAL, 3));
        $this->assertFalse($token->test(Token::LITERAL, array(3, 5)));
        $this->assertFalse($token->test(Token::LITERAL, 'is_string'));

        $this->assertTrue($token->test(Token::LITERAL));
        $this->assertTrue($token->test(Token::LITERAL, 4));
        $this->assertTrue($token->test(Token::LITERAL, 'is_int'));
        $this->assertTrue($token->test(Token::LITERAL, array(4, 5)));
    }
}
