<?php

namespace Modules\Templating\Compiler;

class StreamTest extends \PHPUnit_Framework_TestCase
{

    public function testStreamFunctions()
    {
        // This can be done using integers because this part of Stream does not depend on the type of the data.
        $stream = new Stream(array(1, 2, 3));
        $this->assertEquals(null, $stream->current());
        $this->assertEquals(1, $stream->next());
        $this->assertEquals(2, $stream->next());
        $this->assertEquals(3, $stream->next());
        $this->assertEquals(3, $stream->current());
        $this->assertEquals(2, $stream->prev());
        $this->assertEquals(1, $stream->prev());
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
        $stream = new Stream($tokens);

        $this->assertSame($tokens[0], $stream->expect(Token::IDENTIFIER, 'a'));
        $this->assertSame($tokens[1], $stream->expect(Token::IDENTIFIER, 'b'));
        $this->assertSame($tokens[2], $stream->expect(Token::IDENTIFIER, 'c'));
        $this->assertSame($tokens[2], $stream->expectCurrent(Token::IDENTIFIER, 'c'));
        $this->assertSame($tokens[3], $stream->nextTokenIf(Token::IDENTIFIER, 'd'));
        $this->assertFalse($stream->nextTokenIf(Token::EOF));
    }
}
