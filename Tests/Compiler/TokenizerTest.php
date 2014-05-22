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

        $testTag = $this->getMockBuilder('\\Modules\\Templating\\Compiler\\Tag')
            ->getMockForAbstractClass();
        $testTag->expects($this->any())
            ->method('getTag')
            ->will($this->returnValue('test'));

        $testBlockTag = $this->getMockBuilder('\\Modules\\Templating\\Compiler\\Tag')
            ->setMethods(array('hasEndingTag'))
            ->getMockForAbstractClass();
        $testBlockTag->expects($this->any())
            ->method('getTag')
            ->will($this->returnValue('testblock'));
        $testBlockTag->expects($this->any())
            ->method('hasEndingTag')
            ->will($this->returnValue(true));

        $testPatternTag = $this->getMockBuilder('\\Modules\\Templating\\Compiler\\Tag')
            ->setMethods(array('matches', 'isPatternBased'))
            ->getMockForAbstractClass();
        $testPatternTag->expects($this->any())
            ->method('getTag')
            ->will($this->returnValue('pattern'));
        $testPatternTag->expects($this->any())
            ->method('isPatternBased')
            ->will($this->returnValue(true));
        $testPatternTag->expects($this->any())
            ->method('matches')
            ->will(
                $this->returnCallback(
                    function ($arg) {
                        return $arg === 'pattern based tag';
                    }
                )
            );

        $mockOperator = $this->getMockBuilder('\\Modules\\Templating\\Compiler\\Operator')
            ->setMethods(array('operators'))
            ->setConstructorArgs(array(1))
            ->getMockForAbstractClass();
        $mockOperator->expects($this->any())
            ->method('operators')
            ->will($this->returnValue(array('+', '-')));

        $otherOperator = $this->getMockBuilder('\\Modules\\Templating\\Compiler\\Operator')
            ->setMethods(array('operators'))
            ->setConstructorArgs(array(1))
            ->getMockForAbstractClass();
        $otherOperator->expects($this->any())
            ->method('operators')
            ->will($this->returnValue('oper ator'));

        $mockEnv->getBinaryOperators()->addOperator($mockOperator);
        $mockEnv->getBinaryOperators()->addOperator($otherOperator);
        $mockEnv->addTag($testTag);
        $mockEnv->addTag($testBlockTag);
        $mockEnv->addTag($testPatternTag);

        $this->tokenizer = new Tokenizer($mockEnv);
    }

    public function testTokenizerDoesNotParseTagsInRawBlock()
    {
        $stream = $this->tokenizer->tokenize('{raw}{test}{endraw}');
        $stream->expect(Token::TEXT, '{test}');
        $stream->expect(Token::EOF);
    }

    public function testTokenizerDoesNotParseCommentsInStrings()
    {
        $stream = $this->tokenizer->tokenize("{test 'string {#'}");
        $stream->expect(Token::TAG, 'test');
        $stream->expect(Token::EXPRESSION_START, 'test');
        $stream->expect(Token::STRING, 'string {#');
        $stream->expect(Token::EXPRESSION_END);
        $stream->expect(Token::EOF);
    }

    public function testStringDelimiterIsEscapedCorrectly()
    {
        $stream = $this->tokenizer->tokenize("{test 'string \\' \\\\\\' ' \"string \\\"\"}");
        $stream->expect(Token::TAG, 'test');
        $stream->expect(Token::EXPRESSION_START, 'test');
        $stream->expect(Token::STRING, "string ' \\' ");
        $stream->expect(Token::STRING, 'string "');
        $stream->expect(Token::EXPRESSION_END);
        $stream->expect(Token::EOF);
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

    public function testCommentsAreNotParsedInTags()
    {
        $stream = $this->tokenizer->tokenize('{ test "{# not a comment #}" }');
        $stream->expect(Token::TAG, 'test');
        $stream->expect(Token::EXPRESSION_START, 'test');
        $stream->expect(Token::STRING, '{# not a comment #}');
        $stream->expect(Token::EXPRESSION_END);
        $stream->expect(Token::EOF);
    }

    public function testTagsAreNotParsedInStrings()
    {
        $stream = $this->tokenizer->tokenize('{ test "{not a tag}" }');
        $stream->expect(Token::TAG, 'test');
        $stream->expect(Token::EXPRESSION_START, 'test');
        $stream->expect(Token::STRING, '{not a tag}');
        $stream->expect(Token::EXPRESSION_END);
        $stream->expect(Token::EOF);
    }

    public function testLiteralsAreParsedInTags()
    {
        $stream = $this->tokenizer->tokenize(
            '{ test "string" \'string\' :string true false null 5 5.2 }'
        );
        $stream->expect(Token::TAG, 'test');
        $stream->expect(Token::EXPRESSION_START, 'test');
        $stream->expect(Token::STRING, 'string');
        $stream->expect(Token::STRING, 'string');
        $stream->expect(Token::STRING, 'string');
        $stream->expect(Token::LITERAL, true);
        $stream->expect(Token::LITERAL, false);
        $stream->expect(Token::LITERAL, null);
        $stream->expect(Token::LITERAL, '5');
        $stream->expect(Token::LITERAL, '5.2');
        $stream->expect(Token::EXPRESSION_END);
        $stream->expect(Token::EOF);
    }

    public function testOperatorsAreParsed()
    {
        $stream = $this->tokenizer->tokenize(
            '{ test +- oper ator }'
        );
        $stream->expect(Token::TAG, 'test');
        $stream->expect(Token::EXPRESSION_START, 'test');
        $stream->expect(Token::OPERATOR, '+');
        $stream->expect(Token::OPERATOR, '-');
        $stream->expect(Token::OPERATOR, 'oper ator');
        $stream->expect(Token::EXPRESSION_END);
        $stream->expect(Token::EOF);
    }

    public function testIdentifiersAreParsed()
    {
        $stream = $this->tokenizer->tokenize(
            '{ test ident + ifier }'
        );
        $stream->expect(Token::TAG, 'test');
        $stream->expect(Token::EXPRESSION_START, 'test');
        $stream->expect(Token::IDENTIFIER, 'ident');
        $stream->expect(Token::OPERATOR, '+');
        $stream->expect(Token::IDENTIFIER, 'ifier');
        $stream->expect(Token::EXPRESSION_END);
        $stream->expect(Token::EOF);
    }

    public function testPunctuationIsParsedInTags()
    {
        $stream = $this->tokenizer->tokenize('{ test ?,[]():=> }');
        $stream->expect(Token::TAG, 'test');
        $stream->expect(Token::EXPRESSION_START, 'test');
        $stream->expect(Token::PUNCTUATION, '?');
        $stream->expect(Token::PUNCTUATION, ',');
        $stream->expect(Token::PUNCTUATION, '[');
        $stream->expect(Token::PUNCTUATION, ']');
        $stream->expect(Token::PUNCTUATION, '(');
        $stream->expect(Token::PUNCTUATION, ')');
        $stream->expect(Token::PUNCTUATION, ':');
        $stream->expect(Token::PUNCTUATION, '=>');
        $stream->expect(Token::EXPRESSION_END);
        $stream->expect(Token::EOF);
    }

    public function testClosingTagsAreParsed()
    {
        $stream = $this->tokenizer->tokenize('{ testblock }{ endtestblock }');
        $stream->expect(Token::TAG, 'testblock');
        $stream->expect(Token::EXPRESSION_START, 'testblock');
        $stream->expect(Token::EXPRESSION_END);
        $stream->expect(Token::TAG, 'endtestblock');
        $stream->expect(Token::EOF);
    }

    public function testPatternBasedTagsAreParsed()
    {
        $stream = $this->tokenizer->tokenize('{ pattern based tag }');
        $stream->expect(Token::TAG, 'pattern');
        $stream->expect(Token::EXPRESSION_START, 'pattern');
        $stream->expect(Token::IDENTIFIER, 'pattern');
        $stream->expect(Token::IDENTIFIER, 'based');
        $stream->expect(Token::IDENTIFIER, 'tag');
        $stream->expect(Token::EXPRESSION_END);
        $stream->expect(Token::EOF);
    }

    public function testLinesAreProperlySet()
    {
        $template = 'some text
{test tag}
{test "multiline
string" +
+ tag}
{raw}text
new line
{endraw}{#

 multiline comment

#} {test}
';
        $stream   = $this->tokenizer->tokenize($template);
        $this->assertEquals(1, $stream->expect(Token::TEXT, "some text\n")->getLine());
        $this->assertEquals(2, $stream->expect(Token::TAG, 'test')->getLine());
        $this->assertEquals(2, $stream->expect(Token::EXPRESSION_START, 'test')->getLine());
        $this->assertEquals(2, $stream->expect(Token::IDENTIFIER, 'tag')->getLine());
        $this->assertEquals(2, $stream->expect(Token::EXPRESSION_END)->getLine());
        $this->assertEquals(2, $stream->expect(Token::TEXT, "\n")->getLine());
        $this->assertEquals(3, $stream->expect(Token::TAG, 'test')->getLine());
        $this->assertEquals(3, $stream->expect(Token::EXPRESSION_START, 'test')->getLine());
        $this->assertEquals(3, $stream->expect(Token::STRING, "multiline\nstring")->getLine());
        $this->assertEquals(4, $stream->expect(Token::OPERATOR, '+')->getLine());
        $this->assertEquals(5, $stream->expect(Token::OPERATOR, '+')->getLine());
        $this->assertEquals(5, $stream->expect(Token::IDENTIFIER, "tag")->getLine());
        $this->assertEquals(5, $stream->expect(Token::EXPRESSION_END)->getLine());
        $this->assertEquals(5, $stream->expect(Token::TEXT, "\n")->getLine());
        $this->assertEquals(6, $stream->expect(Token::TEXT, "text\nnew line\n")->getLine());
        $this->assertEquals(8, $stream->expect(Token::TEXT, ' ')->getLine());
        $this->assertEquals(12, $stream->expect(Token::TAG, 'test')->getLine());
        $this->assertEquals(12, $stream->expect(Token::EXPRESSION_START, 'test')->getLine());
        $this->assertEquals(12, $stream->expect(Token::EXPRESSION_END)->getLine());
        $this->assertEquals(12, $stream->expect(Token::TEXT, "\n")->getLine());
        $this->assertEquals(13, $stream->expect(Token::EOF)->getLine());
    }

    public function testCommentsAreRemoved()
    {
        $stream = $this->tokenizer->tokenize('text{# a comment #} foobar');
        $stream->expect(Token::TEXT, 'text foobar');
        $stream->expect(Token::EOF);
    }

    public function unclosedSyntaxProvider()
    {
        return array(
            array('{raw}unclosed raw block{'),
            array('{"unterminated string'),
            array('{"unterminated \\" \\\\\\"'),
            array('{unclosed tag'),
            array('{#unclosed comment'),
        );
    }

    /**
     * @dataProvider unclosedSyntaxProvider
     * @expectedException \Modules\Templating\Compiler\Exceptions\SyntaxException
     */
    public function testTokenizerThrowsExceptions($template)
    {
        $this->tokenizer->tokenize($template);
    }

    /**
     * @expectedException \Modules\Templating\Compiler\Exceptions\ParseException
     */
    public function testTokenizerThrowsParseExceptionForUnknownTags()
    {
        $this->tokenizer->tokenize('{foo tag}');
    }
}
