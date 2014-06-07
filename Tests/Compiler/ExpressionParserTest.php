<?php

namespace Modules\Templating\Compiler;

use Modules\Templating\Compiler\Nodes\DataNode;
use Modules\Templating\Compiler\Nodes\OperatorNode;
use Modules\Templating\Environment;

class ExpressionParserTest extends \PHPUnit_Framework_TestCase
{
    private $plusOperator;
    private $multiplyOperator;
    /**
     * @var Environment
     */
    private $env;
    /**
     * @var ExpressionParser
     */
    private $expressionParser;

    public function setUp()
    {
        $this->env = new Environment();

        $this->plusOperator = $this->getMockBuilder(
            '\\Modules\\Templating\\Compiler\\Operator'
        )
            ->setMethods(array('operators'))
            ->setConstructorArgs(array(1))
            ->getMockForAbstractClass();

        $this->plusOperator->expects($this->any())
            ->method('operators')
            ->will($this->returnValue(array('+')));

        $this->multiplyOperator = $this->getMockBuilder(
            '\\Modules\\Templating\\Compiler\\Operator'
        )
            ->setMethods(array('operators'))
            ->setConstructorArgs(array(2))
            ->getMockForAbstractClass();
        $this->multiplyOperator->expects($this->any())
            ->method('operators')
            ->will($this->returnValue(array('*')));

        $this->env->getBinaryOperators()->addOperator($this->plusOperator);
        $this->env->getBinaryOperators()->addOperator($this->multiplyOperator);

        $this->expressionParser = new ExpressionParser($this->env);
    }

    public function testOperatorPrecedenceIsRespected()
    {
        // 5 * 6 + 7 = (5 * 6) + 7
        $stream = new Stream(array(
            new Token(Token::LITERAL, 5),
            new Token(Token::OPERATOR, '*'),
            new Token(Token::LITERAL, 6),
            new Token(Token::OPERATOR, '+'),
            new Token(Token::LITERAL, 7),
            new Token(Token::EOF),
        ));

        /** @var $multNode OperatorNode */
        /** @var $plusNode OperatorNode */
        /** @var $dataNode DataNode */
        /** @var $multLeftNode DataNode */
        /** @var $multRightNode DataNode */
        $plusNode = $this->expressionParser->parse($stream);
        $multNode = $plusNode->getChild(OperatorNode::OPERAND_LEFT);

        $multLeftNode  = $multNode->getChild(OperatorNode::OPERAND_LEFT);
        $multRightNode = $multNode->getChild(OperatorNode::OPERAND_RIGHT);
        $dataNode      = $plusNode->getChild(OperatorNode::OPERAND_RIGHT);

        $this->assertSame($this->multiplyOperator, $multNode->getOperator());
        $this->assertSame($this->plusOperator, $plusNode->getOperator());
        $this->assertEquals(5, $multLeftNode->getData());
        $this->assertEquals(6, $multRightNode->getData());
        $this->assertEquals(7, $dataNode->getData());
    }

    public function testOperatorPrecedenceCanBeOverriddenByParentheses()
    {
        // 5 * (6 + 7)
        $stream = new Stream(array(
            new Token(Token::LITERAL, 5),
            new Token(Token::OPERATOR, '*'),
            new Token(Token::PUNCTUATION, '('),
            new Token(Token::LITERAL, 6),
            new Token(Token::OPERATOR, '+'),
            new Token(Token::LITERAL, 7),
            new Token(Token::PUNCTUATION, ')'),
            new Token(Token::EOF),
        ));

        /** @var $multNode OperatorNode */
        /** @var $plusNode OperatorNode */
        /** @var $dataNode DataNode */
        /** @var $plusLeftNode DataNode */
        /** @var $plusRightNode DataNode */
        $multNode = $this->expressionParser->parse($stream);
        $plusNode = $multNode->getChild(OperatorNode::OPERAND_RIGHT);

        $dataNode      = $multNode->getChild(OperatorNode::OPERAND_LEFT);
        $plusLeftNode  = $plusNode->getChild(OperatorNode::OPERAND_LEFT);
        $plusRightNode = $plusNode->getChild(OperatorNode::OPERAND_RIGHT);

        $this->assertSame($this->multiplyOperator, $multNode->getOperator());
        $this->assertSame($this->plusOperator, $plusNode->getOperator());
        $this->assertEquals(5, $dataNode->getData());
        $this->assertEquals(6, $plusLeftNode->getData());
        $this->assertEquals(7, $plusRightNode->getData());
    }
}
