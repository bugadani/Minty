<?php

namespace Minty\Compiler;

class StreamTest extends \PHPUnit_Framework_TestCase
{

    /**
     * @param $tokens
     *
     * @return Stream
     */
    public function createStream($tokens)
    {
        $stream = new Stream();
        foreach ($tokens as $token) {
            $stream->push($token);
        }
        $stream->rewind();

        return $stream;
    }

    public function testStreamFunctions()
    {
        $tokens = array(
            new Token(Token::IDENTIFIER, 1),
            new Token(Token::IDENTIFIER, 2),
            new Token(Token::IDENTIFIER, 3),
        );
        $stream = $this->createStream($tokens);
        $this->assertEquals(null, $stream->current());
        $this->assertEquals(1, $stream->next()->getValue());
        $this->assertEquals(2, $stream->next()->getValue());
        $this->assertEquals(3, $stream->next()->getValue());
        $this->assertEquals(3, $stream->current()->getValue());
    }

    public function testTestFunctions()
    {
        $tokens = array(
            new Token(Token::IDENTIFIER, 'a'),
            new Token(Token::IDENTIFIER, 'b'),
            new Token(Token::IDENTIFIER, 'c'),
            new Token(Token::IDENTIFIER, 'd'),
            new Token(Token::IDENTIFIER, 'e'),
        );
        $stream = $this->createStream($tokens);

        $this->assertSame($tokens[0], $stream->expect(Token::IDENTIFIER, 'a'));
        $this->assertSame($tokens[1], $stream->expect(Token::IDENTIFIER, 'b'));
        $this->assertSame($tokens[2], $stream->expect(Token::IDENTIFIER, 'c'));
        $this->assertSame($tokens[2], $stream->expectCurrent(Token::IDENTIFIER, 'c'));
        $this->assertSame($tokens[3], $stream->nextTokenIf(Token::IDENTIFIER, 'd'));
        $this->assertFalse($stream->nextTokenIf(Token::EOF));
    }

    /**
     * @expectedException \Minty\Compiler\Exceptions\SyntaxException
     */
    public function testThatFailedExpectationThrowsException()
    {
        $tokens = array(new Token(Token::EOF));
        $stream = $this->createStream($tokens);
        $stream->expect(Token::IDENTIFIER);
    }
}
