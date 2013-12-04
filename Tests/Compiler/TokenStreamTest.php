<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating;

use Modules\Templating\Compiler\Token;
use Modules\Templating\Compiler\TokenStream;
use PHPUnit_Framework_TestCase;

require_once __DIR__ . '/../../Compiler/Token.php';
require_once __DIR__ . '/../../Compiler/TokenStream.php';
require_once __DIR__ . '/../../Compiler/SyntaxException.php';

class TokenStreamTest extends PHPUnit_Framework_TestCase
{

    public function testTokenPop()
    {
        $stream = new TokenStream();
        $stream->push(new Token(Token::LITERAL, '1'));
        $stream->push(new Token(Token::LITERAL, '2'));
        $stream->push(new Token(Token::LITERAL, '3'));
        $this->assertEquals('3', $stream->pop()->getValue());
        $this->assertEquals('2', $stream->pop()->getValue());
        $this->assertEquals('1', $stream->pop()->getValue());
        $this->assertEquals(null, $stream->pop());
    }

    public function testTextTokenMerge()
    {
        $stream   = new TokenStream();
        $stream->push(new Token(Token::TEXT, '1'));
        $stream->push(new Token(Token::TEXT, '2'));
        $stream->push(new Token(Token::TEXT, '3'));
        $expected = array(
            new Token(Token::TEXT, '123')
        );
        $this->assertEquals($expected, $stream->getTokens());
    }

    public function testPassingSimpleExpectations()
    {
        $stream = new TokenStream();
        $stream->expect(Token::TEXT, '1');
        $stream->expect(Token::TEXT, '2');
        $stream->push(new Token(Token::TEXT, '2'));
        //this test should not throw exceptions
    }

    /**
     * @expectedException Modules\Templating\Compiler\SyntaxException
     */
    public function testFailingSimpleExpectation()
    {
        $stream = new TokenStream();
        $stream->expect(Token::TEXT, '1');
        $stream->push(new Token(Token::EOF));
    }

    /**
     * @expectedException Modules\Templating\Compiler\SyntaxException
     */
    public function testConsumeWhitespaceWithExpectation()
    {
        $stream = new TokenStream();
        $stream->expect(Token::EOF);
        $stream->consumeNextWhitespace();

        $stream->push(new Token(Token::TEXT, 'some text'));
    }

    public function testExpirationOfExpectation()
    {
        $stream = new TokenStream();
        $stream->expect(Token::TEXT, null, 0);
        $this->assertTrue($stream->hasExpectations());
        $stream->push(new Token(Token::TEXT, 'should not be empty'));
        $stream->push(new Token(Token::TAG));

        $this->assertFalse($stream->hasExpectations());
        //should not throw exceptions
    }

    public function testPassingMultipleExpectation()
    {
        $stream = new TokenStream();
        $stream->expect(Token::TEXT, '1');
        $stream->expect(Token::ARGUMENT_LIST_END);
        $stream->push(new Token(Token::TEXT, '1'));

        $stream->expect(Token::TEXT, '1');
        $stream->expect(Token::ARGUMENT_LIST_END);
        $stream->push(new Token(Token::ARGUMENT_LIST_END));
    }

    /**
     * @expectedException Modules\Templating\Compiler\SyntaxException
     */
    public function testFailingMultipleExpectation()
    {
        $stream = new TokenStream();
        $stream->expect(Token::TEXT, '1');
        $stream->expect(Token::ARGUMENT_LIST_END);
        $stream->push(new Token(Token::EOF));
    }

    public function testPassingLaterExpectation()
    {
        $stream = new TokenStream();
        $stream->expect(Token::TEXT, null, 2);
        $stream->push(new Token(Token::EOF));
        $stream->push(new Token(Token::TEXT, 'should not be empty'));
    }

    public function testPassingNegativeExpectation()
    {
        $stream = new TokenStream();
        $stream->expect(Token::TEXT, null, 1, true);

        $stream->push(new Token(Token::EOF, '1'));
    }

    /**
     * @expectedException Modules\Templating\Compiler\SyntaxException
     */
    public function testFailingNegativeExpectation()
    {
        $stream = new TokenStream();
        $stream->expect(Token::TEXT, null, 1, true);

        $stream->push(new Token(Token::TEXT, 'should not be empty'));
    }

    /**
     * @expectedException Modules\Templating\Compiler\SyntaxException
     */
    public function testFailingLaterNegativeExpectation()
    {
        $stream = new TokenStream();
        $stream->expect(Token::EOF, null, 2, true);
        $stream->push(new Token(Token::TEXT, 'should not be empty'));
        $stream->push(new Token(Token::EOF));
    }

    public function testPassingExpectationBranch()
    {
        $stream = new TokenStream();
        $stream->expect(Token::TEXT)->then(Token::EOF);
        $stream->expect(Token::TEXT)->then(Token::ARGUMENT_LIST_START)->then(Token::TEXT);

        $stream->push(new Token(Token::TEXT, 'should not be empty'));
        $stream->push(new Token(Token::EOF));

        $stream->expect(Token::TEXT)->then(Token::EOF);
        $stream->expect(Token::TEXT)->then(Token::ARGUMENT_LIST_START)->then(Token::TEXT);

        $stream->push(new Token(Token::TEXT, 'should not be empty'));
        $stream->push(new Token(Token::ARGUMENT_LIST_START));
        $stream->push(new Token(Token::TEXT, 'not empty'));

        $stream->expect(Token::TEXT)->then(Token::EOF);
        $stream->expect(Token::TEXT)->then(Token::ARGUMENT_LIST_START)->then(Token::TEXT);

        $stream->push(new Token(Token::TEXT, 'should not be empty'));
        $stream->push(new Token(Token::EOF));
        $stream->push(new Token(Token::EOF));
    }

    /**
     * @expectedException Modules\Templating\Compiler\SyntaxException
     */
    public function testFailingExpectationBranch()
    {
        $stream = new TokenStream();
        $stream->expect(Token::TEXT)->then(Token::EOF);
        $stream->expect(Token::TEXT)->then(Token::ARGUMENT_LIST_START)->then(Token::TEXT);

        $stream->push(new Token(Token::TEXT, 'should not be empty'));
        $stream->push(new Token(Token::ARGUMENT_LIST_START));
        $stream->push(new Token(Token::EOF));
    }

    public function testPassingMultipleNegativeExpectation()
    {
        $stream = new TokenStream();
        $stream->expect(Token::TEXT, '1', null, true);
        $stream->expect(Token::ARGUMENT_LIST_END, null, true);
        $stream->push(new Token(Token::EOF));
    }

    /**
     * @expectedException Modules\Templating\Compiler\SyntaxException
     */
    public function testFailingMultipleNegativeExpectation()
    {
        $stream = new TokenStream();
        $stream->expect(Token::ARGUMENT_LIST_END, null, true)->also(Token::EOF, null, null, true);
        $stream->push(new Token(Token::EOF));
    }
}
