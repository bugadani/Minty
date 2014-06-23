<?php

namespace Minty\Compiler;

use Minty\Environment;

class TokenizerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Tokenizer
     */
    private $tokenizer;

    /**
     * @var Environment
     */
    private $environment;
    private $mockLoader;

    public function setUp()
    {
        $this->mockLoader = $this->getMockForAbstractClass(
            '\\Minty\\AbstractTemplateLoader'
        );

        $this->mockLoader->expects($this->any())
            ->method('getCacheKey')
            ->will($this->returnArgument(0));

        $mockEnv = new Environment($this->mockLoader, array('fallback_tag' => 'fallback'));

        $testTag = $this->getMockBuilder('\\Minty\\Compiler\\Tag')
            ->getMockForAbstractClass();
        $testTag->expects($this->any())
            ->method('getTag')
            ->will($this->returnValue('test'));

        $testBlockTag = $this->getMockBuilder('\\Minty\\Compiler\\Tag')
            ->setMethods(array('hasEndingTag'))
            ->getMockForAbstractClass();
        $testBlockTag->expects($this->any())
            ->method('getTag')
            ->will($this->returnValue('testblock'));
        $testBlockTag->expects($this->any())
            ->method('hasEndingTag')
            ->will($this->returnValue(true));

        $mockOperator = $this->getMockBuilder('\\Minty\\Compiler\\Operator')
            ->setMethods(array('operators'))
            ->setConstructorArgs(array(1))
            ->getMockForAbstractClass();
        $mockOperator->expects($this->any())
            ->method('operators')
            ->will($this->returnValue(array('§', '-')));

        $otherOperator = $this->getMockBuilder('\\Minty\\Compiler\\Operator')
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
        $mockEnv->addFunction(
            new TemplateFunction('test', function () {
            })
        );

        $this->tokenizer   = new Tokenizer($mockEnv);
        $this->environment = $mockEnv;
    }

    public function testTokenizerIsAbleToHandleVeryLongTemplates()
    {
        $template = str_repeat('foo', 100000);
        $template .= '{raw}';
        $template .= str_repeat('foo', 100000);
        $template .= '{/raw}{#';
        $template .= str_repeat('foo', 100000);
        $template .= '#}';
        $this->tokenizer->tokenize($template);
    }

    public function testTokenizerDoesNotParseTagsInRawBlock()
    {
        $stream = $this->tokenizer->tokenize('{ raw }{test}{ /raw }');
        $stream->expect(Token::TEXT, '{test}');
        $stream->expect(Token::EOF);
    }

    public function testTokenizerDoesNotParseCommentsInStrings()
    {
        $stream = $this->tokenizer->tokenize("{test 'string {#'}");
        $stream->expect(Token::TAG, 'test');
        $stream->expect(Token::EXPRESSION_START);
        $stream->expect(Token::STRING, 'string {#');
        $stream->expect(Token::EXPRESSION_END);
        $stream->expect(Token::EOF);
    }

    public function testTokenizerDoesNotParseVariablesInStrings()
    {
        $stream = $this->tokenizer->tokenize('{test "string $var"}');
        $stream->expect(Token::TAG, 'test');
        $stream->expect(Token::EXPRESSION_START);
        $stream->expect(Token::STRING, 'string $var');
        $stream->expect(Token::EXPRESSION_END);
        $stream->expect(Token::EOF);
    }

    public function testStringDelimiterIsEscapedCorrectly()
    {
        $stream = $this->tokenizer->tokenize("{test 'string \\' \\\\\\' ' \"string \\\"\"}");
        $stream->expect(Token::TAG, 'test');
        $stream->expect(Token::EXPRESSION_START);
        $stream->expect(Token::STRING, "string ' \\' ");
        $stream->expect(Token::STRING, 'string "');
        $stream->expect(Token::EXPRESSION_END);
        $stream->expect(Token::EOF);
    }

    public function testEscapeCharactersAreInString()
    {
        $stream = $this->tokenizer->tokenize("{test ' \n '}");
        $stream->expect(Token::TAG, 'test');
        $stream->expect(Token::EXPRESSION_START);
        $stream->expect(Token::STRING, " \n ");
        $stream->expect(Token::EXPRESSION_END);
        $stream->expect(Token::EOF);
    }

    public function testTokenizerAcceptsCustomBlockClosingTagPrefix()
    {
        $env       = new Environment($this->mockLoader, array(
            'block_end_prefix' => 'end'
        ));
        $tokenizer = new Tokenizer($env);

        return $tokenizer->tokenize('{raw}some random content {}{endraw{endraw}');
    }

    /**
     * @depends testTokenizerAcceptsCustomBlockClosingTagPrefix
     */
    public function testRawBlocksAreNotParsed(Stream $stream)
    {
        $this->assertEquals('some random content {}{endraw', $stream->next()->getValue());
    }

    public function testCommentsAreNotRemovedFromRawBlocks()
    {
        $stream = $this->tokenizer->tokenize('{raw}something {# comment #} something{/raw}');

        $this->assertEquals('something {# comment #} something', $stream->next()->getValue());
    }

    public function testCommentsAreNotParsedInTags()
    {
        $stream = $this->tokenizer->tokenize('{ test "{# not a comment #}" }');
        $stream->expect(Token::TAG, 'test');
        $stream->expect(Token::EXPRESSION_START);
        $stream->expect(Token::STRING, '{# not a comment #}');
        $stream->expect(Token::EXPRESSION_END);
        $stream->expect(Token::EOF);
    }

    public function testTagsAreNotParsedInStrings()
    {
        $stream = $this->tokenizer->tokenize('{ test "{not a tag}" }');
        $stream->expect(Token::TAG, 'test');
        $stream->expect(Token::EXPRESSION_START);
        $stream->expect(Token::STRING, '{not a tag}');
        $stream->expect(Token::EXPRESSION_END);
        $stream->expect(Token::EOF);
    }

    public function testLiteralsAreParsedInTags()
    {
        $stream = $this->tokenizer->tokenize(
            '{ test "string" \'string\' :string_underscore true false null 5 5.2 }'
        );
        $stream->expect(Token::TAG, 'test');
        $stream->expect(Token::EXPRESSION_START);
        $stream->expect(Token::STRING, 'string');
        $stream->expect(Token::STRING, 'string');
        $stream->expect(Token::STRING, 'string_underscore');
        $stream->expect(Token::LITERAL, true);
        $stream->expect(Token::LITERAL, false);
        $stream->expect(Token::LITERAL, null);
        $stream->expect(Token::LITERAL, 5);
        $stream->expect(Token::LITERAL, 5.2);
        $stream->expect(Token::EXPRESSION_END);
        $stream->expect(Token::EOF);
    }

    public function testNumbersAreAllowedInShortStrings()
    {
        $stream = $this->tokenizer->tokenize('{ test :123 }');
        $stream->expect(Token::TAG, 'test');
        $stream->expect(Token::EXPRESSION_START);
        $stream->expect(Token::STRING, '123');
        $stream->expect(Token::EXPRESSION_END);
        $stream->expect(Token::EOF);
    }

    public function testOperatorsAreParsed()
    {
        $stream = $this->tokenizer->tokenize(
            '{ test §- oper ator }'
        );
        $stream->expect(Token::TAG, 'test');
        $stream->expect(Token::EXPRESSION_START);
        $stream->expect(Token::OPERATOR, '§');
        $stream->expect(Token::OPERATOR, '-');
        $stream->expect(Token::OPERATOR, 'oper ator');
        $stream->expect(Token::EXPRESSION_END);
        $stream->expect(Token::EOF);
    }

    public function testIdentifiersAreParsed()
    {
        $stream = $this->tokenizer->tokenize(
            '{ test ident § ifier § $variable § $var_underscore }'
        );
        $stream->expect(Token::TAG, 'test');
        $stream->expect(Token::EXPRESSION_START);
        $stream->expect(Token::IDENTIFIER, 'ident');
        $stream->expect(Token::OPERATOR, '§');
        $stream->expect(Token::IDENTIFIER, 'ifier');
        $stream->expect(Token::OPERATOR, '§');
        $stream->expect(Token::VARIABLE, 'variable');
        $stream->expect(Token::OPERATOR, '§');
        $stream->expect(Token::VARIABLE, 'var_underscore');
        $stream->expect(Token::EXPRESSION_END);
        $stream->expect(Token::EOF);
    }

    public function testPunctuationIsParsedInTags()
    {
        $stream = $this->tokenizer->tokenize('{ test ?,[]():=> }');
        $stream->expect(Token::TAG, 'test');
        $stream->expect(Token::EXPRESSION_START);
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

    public function testStringsAreOnlyParsedInTags()
    {
        $stream = $this->tokenizer->tokenize('"{ test }"');
        $stream->expect(Token::TEXT, '"');
        $stream->expect(Token::TAG, 'test');
        $stream->expect(Token::TEXT, '"');
        $stream->expect(Token::EOF);
    }

    public function testClosingTagsAreParsed()
    {
        $stream = $this->tokenizer->tokenize('{ testblock }{ /testblock }');
        $stream->expect(Token::TAG, 'testblock');
        $stream->expect(Token::TAG, 'endtestblock');
        $stream->expect(Token::EOF);
    }

    public function testLinesAreProperlySet()
    {
        $template = 'some text
{test tag}
{test "multiline
string" §
§ tag}
{raw}text
new line
{/raw} {#

 multiline comment

#} {test      }
';
        $stream   = $this->tokenizer->tokenize($template);
        $this->assertEquals(1, $stream->expect(Token::TEXT, "some text\n")->getLine());
        $this->assertEquals(2, $stream->expect(Token::TAG, 'test')->getLine());
        $this->assertEquals(2, $stream->expect(Token::EXPRESSION_START)->getLine());
        $this->assertEquals(2, $stream->expect(Token::IDENTIFIER, 'tag')->getLine());
        $this->assertEquals(2, $stream->expect(Token::EXPRESSION_END)->getLine());
        $this->assertEquals(2, $stream->expect(Token::TEXT, "\n")->getLine());
        $this->assertEquals(3, $stream->expect(Token::TAG, 'test')->getLine());
        $this->assertEquals(3, $stream->expect(Token::EXPRESSION_START)->getLine());
        $this->assertEquals(3, $stream->expect(Token::STRING, "multiline\nstring")->getLine());
        $this->assertEquals(4, $stream->expect(Token::OPERATOR, '§')->getLine());
        $this->assertEquals(5, $stream->expect(Token::OPERATOR, '§')->getLine());
        $this->assertEquals(5, $stream->expect(Token::IDENTIFIER, "tag")->getLine());
        $this->assertEquals(5, $stream->expect(Token::EXPRESSION_END)->getLine());
        $this->assertEquals(5, $stream->expect(Token::TEXT, "\n")->getLine());
        $this->assertEquals(6, $stream->expect(Token::TEXT, "text\nnew line\n")->getLine());
        $this->assertEquals(8, $stream->expect(Token::TEXT, ' ')->getLine());
        $this->assertEquals(12, $stream->expect(Token::TEXT, ' ')->getLine());
        $this->assertEquals(12, $stream->expect(Token::TAG, 'test')->getLine());
        $this->assertEquals(12, $stream->expect(Token::TEXT, "\n")->getLine());
        $this->assertEquals(13, $stream->expect(Token::EOF)->getLine());
    }

    public function testCommentsAreRemoved()
    {
        $stream = $this->tokenizer->tokenize('text{# a comment #} foobar');
        $stream->expect(Token::TEXT, 'text');
        $stream->expect(Token::TEXT, ' foobar');
        $stream->expect(Token::EOF);
    }

    public function unclosedSyntaxProvider()
    {
        return array(
            array('{raw}unclosed raw block{'),
            array('{raw}unclosed raw block{/raw'),
            array('{"unterminated string'),
            array('{"unterminated \\" \\\\\\"'),
            array('{unclosed tag'),
            array('{#unclosed comment'),
        );
    }

    /**
     * @dataProvider unclosedSyntaxProvider
     * @expectedException \Minty\Compiler\Exceptions\SyntaxException
     */
    public function testTokenizerThrowsExceptions($template)
    {
        $this->tokenizer->tokenize($template);
    }

    public function testTokenizerUsesFallbackTagForUnknownTags()
    {
        $fallbackTag = $this->getMockBuilder('\\Minty\\Compiler\\Tag')
            ->getMockForAbstractClass();
        $fallbackTag->expects($this->any())
            ->method('getTag')
            ->will($this->returnValue('fallback'));

        $this->environment->addTag($fallbackTag);

        $tokenizer = new Tokenizer($this->environment);
        $tokenizer->tokenize('{foo tag}');

        return $tokenizer;
    }

    /**
     * @depends testTokenizerUsesFallbackTagForUnknownTags
     */
    public function testFunctionWithSameNameAsTagIsNotTokenizedAsTag(Tokenizer $tokenizer)
    {
        $stream = $tokenizer->tokenize('{test()}');
        $stream->expect(Token::TAG, 'fallback');
        $stream->expect(Token::EXPRESSION_START);
        $stream->expect(Token::IDENTIFIER, 'test');
        $stream->expect(Token::PUNCTUATION, '(');
        $stream->expect(Token::PUNCTUATION, ')');
        $stream->expect(Token::EXPRESSION_END);
        $stream->expect(Token::EOF);

        return $tokenizer;
    }

    /**
     * @depends testFunctionWithSameNameAsTagIsNotTokenizedAsTag
     */
    public function testEmptyTagIsAValidFallbackTag(Tokenizer $tokenizer)
    {
        $stream = $tokenizer->tokenize('{ }');
        $stream->expect(Token::TAG, 'fallback');
        $stream->expect(Token::EOF);

        return $tokenizer;
    }

    /**
     * @depends testEmptyTagIsAValidFallbackTag
     */
    public function testStringIsCorrectlyParsedInTag(Tokenizer $tokenizer)
    {

        $stream = $tokenizer->tokenize('"{ "test" }"');
        $stream->expect(Token::TEXT, '"');
        $stream->expect(Token::TAG, 'fallback');
        $stream->expect(Token::EXPRESSION_START);
        $stream->expect(Token::STRING, 'test');
        $stream->expect(Token::EXPRESSION_END);
        $stream->expect(Token::TEXT, '"');
        $stream->expect(Token::EOF);
    }

    public function testLiteralsAreNotSplitFromIdentifiersAndVariables()
    {

        $stream = $this->tokenizer->tokenize('{ test nullable $true }');
        $stream->expect(Token::TAG, 'test');
        $stream->expect(Token::EXPRESSION_START);
        $stream->expect(Token::IDENTIFIER, 'nullable');
        $stream->expect(Token::VARIABLE, 'true');
        $stream->expect(Token::EXPRESSION_END);
        $stream->expect(Token::EOF);
    }
}
