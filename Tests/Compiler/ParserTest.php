<?php

namespace Modules\Templating\Compiler;

use Modules\Templating\Environment;

class ParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Environment
     */
    private $env;

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
        $this->env                  = new Environment();
        $this->expressionParserMock = $this->getMockBuilder(
            '\\Modules\\Templating\\Compiler\\ExpressionParser'
        )
            ->disableOriginalConstructor()
            ->setMethods(array('parse'))
            ->getMock();

        $this->parser = new Parser($this->env, $this->expressionParserMock);
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
}
