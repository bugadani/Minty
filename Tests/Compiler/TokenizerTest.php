<?php

namespace Modules\Templating\Compiler;

use Modules\Templating\Environment;

class TokenizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Tokenizer
     */
    private $tokenizer;

    public function setUp()
    {
        $mockEnv = new Environment();

        $this->tokenizer = new Tokenizer($mockEnv);
    }

    /**
     * @expectedException \Modules\Templating\Compiler\Exceptions\SyntaxException
     */
    public function testTokenizerThrowsExceptionOnUnclosedRawBlock()
    {
        $this->tokenizer->tokenize('{raw}');
    }

    public function testTokenizerDoesNotThrowExceptionOnClosedRawBlock()
    {
        $this->tokenizer->tokenize('{raw}{endraw}');
    }

    public function testTokenizerAcceptsCustomBlockClosingTagPrefix()
    {
        $env       = new Environment(array(
            'block_end_prefix' => '/'
        ));
        $tokenizer = new Tokenizer($env);

        return $tokenizer->tokenize('{raw}some random content {}{/raw{/raw}');
    }

    /**
     * @depends testTokenizerAcceptsCustomBlockClosingTagPrefix
     */
    public function testRawBlocksAreNotParsed(Stream $stream)
    {
        $this->assertEquals('some random content {}{/raw', $stream->next()->getValue());
    }

    public function testCommentsAreNotRemovedFromRawBlocks()
    {
        $stream = $this->tokenizer->tokenize('{raw}something {# comment #} something{endraw}');

        $this->assertEquals('something {# comment #} something', $stream->next()->getValue());
    }
}
