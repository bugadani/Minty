<?php

namespace Minty\Compiler;

class StreamTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Tokenizer
     */
    private $mockTokenizer;

    public function setUp()
    {
        $this->mockTokenizer = $this->getMockBuilder('Minty\\Compiler\\Tokenizer')
            ->setMethods(['nextToken'])
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @expectedException \Minty\Compiler\Exceptions\SyntaxException
     */
    public function testExpectTypeException()
    {
        $this->mockTokenizer->expects($this->exactly(2))
            ->method('nextToken')
            ->will(
                $this->onConsecutiveCalls(
                    new Token(Token::IDENTIFIER),
                    new Token(Token::EOF)
                )
            );

        $stream = new Stream($this->mockTokenizer);
        $stream->expect(Token::LITERAL);
    }

    /**
     * @expectedException \Minty\Compiler\Exceptions\SyntaxException
     */
    public function testExpectValueException()
    {
        $this->mockTokenizer->expects($this->exactly(2))
            ->method('nextToken')
            ->will(
                $this->onConsecutiveCalls(
                    new Token(Token::IDENTIFIER, 'foo'),
                    new Token(Token::IDENTIFIER, 'bar')
                )
            );

        $stream = new Stream($this->mockTokenizer);
        $stream->expect(Token::IDENTIFIER, 'bar');
    }

    public function testExpectStepsToNextToken()
    {
        $token1 = new Token(Token::IDENTIFIER, 'foo');
        $token2 = new Token(Token::IDENTIFIER, 'bar');
        $this->mockTokenizer->expects($this->exactly(3))
            ->method('nextToken')
            ->will(
                $this->onConsecutiveCalls(
                    $token1,
                    $token2,
                    new Token(Token::EOF)
                )
            );

        $stream = new Stream($this->mockTokenizer);
        $this->assertSame($token1, $stream->expect(Token::IDENTIFIER, 'foo'));
        $this->assertSame($token2, $stream->expect(Token::IDENTIFIER, 'bar'));
    }
}
