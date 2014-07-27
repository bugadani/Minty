<?php

namespace Minty\Compiler;

use Minty\Environment;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Environment
     */
    private $env;

    /**
     * @var Tag|\PHPUnit_Framework_MockObject_MockObject
     */
    private $tag;

    /**
     * @var Tag|\PHPUnit_Framework_MockObject_MockObject
     */
    private $blockTag;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ExpressionParser
     */
    private $expressionParserMock;

    public function setUp()
    {
        $mockLoader = $this->getMockForAbstractClass(
            '\\Minty\\AbstractTemplateLoader'
        );

        $mockLoader->expects($this->any())
            ->method('getCacheKey')
            ->will($this->returnArgument(0));

        $this->env = new Environment($mockLoader);

        $this->expressionParserMock = $this->getMockBuilder(
            '\\Minty\\Compiler\\ExpressionParser'
        )
            ->disableOriginalConstructor()
            ->setMethods(array('parse'))
            ->getMock();

        $this->tag = $this->getMockBuilder('\\Minty\\Compiler\\Tag')
            ->setMethods(array('parse'))
            ->getMockForAbstractClass();
        $this->tag->expects($this->any())
            ->method('getTag')
            ->will($this->returnValue('test'));

        $this->blockTag = $this->getMockBuilder('\\Minty\\Compiler\\Tag')
            ->setMethods(array('hasEndingTag', 'parse'))
            ->getMockForAbstractClass();
        $this->blockTag->expects($this->any())
            ->method('getTag')
            ->will($this->returnValue('testblock'));
        $this->blockTag->expects($this->any())
            ->method('hasEndingTag')
            ->will($this->returnValue(true));

        $this->env->addTag($this->tag);
        $this->env->addTag($this->blockTag);

        $this->parser = new Parser($this->env, $this->expressionParserMock);
    }

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

    public function testThatEnvironmentIsSet()
    {
        $this->assertSame($this->env, $this->parser->getEnvironment());
    }

    public function testThatExpressionParserIsCalled()
    {
        $this->expressionParserMock
            ->expects($this->once())
            ->method('parse');

        $this->parser->parseExpression(new Stream());
    }

    public function testExpressionStartTokenForTagIsSkipped()
    {
        $stream = $this->createStream(array(
            new Token(Token::TAG, 'test'),
            new Token(Token::EXPRESSION_START),
            new Token(Token::TAG, 'test'),
            new Token(Token::TAG, 'test'),
            new Token(Token::EOF)
        ));

        $this->tag
            ->expects($this->exactly(3))
            ->method('parse');

        $this->parser->parseTemplate($stream, 'foo', 'foo');
    }

    public function testParseBlockStopsAtClosingTag()
    {
        $stream = $this->createStream(array(
            new Token(Token::TAG, 'testblock'),
            new Token(Token::TAG, 'test'),
            new Token(Token::TEXT, 'this will be skipped'),
            new Token(Token::TAG, 'endtestblock'),
            new Token(Token::EOF)
        ));

        $this->parser->parseBlock($stream, 'endtestblock');
        $this->assertTrue($stream->current()->test(Token::TAG, 'endtestblock'));
    }

    public function testMainScopeChangesToFalseInBlocks()
    {
        $test = $this;
        $this->tag->expects($this->once())
            ->method('parse')
            ->will(
                $this->returnCallback(
                    function (Parser $parser) use ($test) {
                        $test->assertFalse($parser->inMainScope());
                    }
                )
            );
        $this->blockTag->expects($this->once())
            ->method('parse')
            ->will(
                $this->returnCallback(
                    function (Parser $parser, Stream $stream) use ($test) {
                        $test->assertTrue($parser->inMainScope());
                        $parser->parseBlock($stream, 'endtestblock');
                    }
                )
            );
        $stream = $this->createStream(array(
            new Token(Token::TAG, 'testblock'),
            new Token(Token::TAG, 'test'),
            new Token(Token::TEXT, 'this will be skipped'),
            new Token(Token::TAG, 'endtestblock'),
            new Token(Token::EOF)
        ));

        $this->parser->parseTemplate($stream, 'foo', 'foo');
    }

    /**
     * @expectedException \Minty\Compiler\Exceptions\ParseException
     */
    public function testExceptionIsThrownForUnknownTags()
    {
        $stream = $this->createStream(array(
            new Token(Token::TAG, 'footag'),
            new Token(Token::EOF)
        ));
        $this->parser->parseTemplate($stream, 'foo', 'foo');
    }

    /**
     * @expectedException \Minty\Compiler\Exceptions\ParseException
     */
    public function testExceptionIsThrownForTokensThatAreNotExpected()
    {
        $stream = $this->createStream(array(
            new Token(Token::LITERAL, 'foo'),
            new Token(Token::EOF)
        ));
        $this->parser->parseTemplate($stream, 'foo', 'foo');
    }

    /**
     * @expectedException \Minty\Compiler\Exceptions\ParseException
     */
    public function testExceptionIsThrownWhenNotInBlock()
    {
        $this->parser->getCurrentBlock();
    }

    public function testBlocksCanBeNested()
    {
        $this->parser->enterBlock('a');
        $this->parser->enterBlock('b');
        $this->parser->enterBlock('c');
        $this->assertEquals('c', $this->parser->getCurrentBlock());
        $this->parser->leaveBlock();
        $this->assertEquals('b', $this->parser->getCurrentBlock());
        $this->parser->leaveBlock();
        $this->assertEquals('a', $this->parser->getCurrentBlock());
        $this->parser->leaveBlock();
    }
}
