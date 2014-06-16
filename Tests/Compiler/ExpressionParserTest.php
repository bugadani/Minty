<?php

namespace Modules\Templating\Compiler;

use Modules\Templating\Compiler\Nodes\ArrayIndexNode;
use Modules\Templating\Compiler\Nodes\DataNode;
use Modules\Templating\Compiler\Nodes\FunctionNode;
use Modules\Templating\Compiler\Nodes\OperatorNode;
use Modules\Templating\Compiler\Nodes\VariableNode;
use Modules\Templating\Environment;
use Modules\Templating\TemplateLoaders\ChainLoader;

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
        $this->assertEquals(5, $multLeftNode->getValue());
        $this->assertEquals(6, $multRightNode->getValue());
        $this->assertEquals(7, $dataNode->getValue());
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
        $this->assertEquals(5, $dataNode->getValue());
        $this->assertEquals(6, $plusLeftNode->getValue());
        $this->assertEquals(7, $plusRightNode->getValue());
    }

    public function simpleArrayProvider()
    {
        return array(
            array(
                new Stream(array(
                    new Token(Token::PUNCTUATION, '['),
                    new Token(Token::STRING, 'foo'),
                    new Token(Token::PUNCTUATION, ':'),
                    new Token(Token::STRING, 'bar'),
                    new Token(Token::PUNCTUATION, ']'),
                    new Token(Token::EOF)
                ))
            ),
            array(
                new Stream(array(
                    new Token(Token::PUNCTUATION, '['),
                    new Token(Token::STRING, 'foo'),
                    new Token(Token::PUNCTUATION, '=>'),
                    new Token(Token::STRING, 'bar'),
                    new Token(Token::PUNCTUATION, ']'),
                    new Token(Token::EOF)
                ))
            ),
            array(
                new Stream(array(
                    new Token(Token::PUNCTUATION, '['),
                    new Token(Token::STRING, 'foo'),
                    new Token(Token::PUNCTUATION, ','),
                    new Token(Token::STRING, 'bar'),
                    new Token(Token::PUNCTUATION, ']'),
                    new Token(Token::EOF)
                ))
            ),
            array(
                new Stream(array(
                    new Token(Token::PUNCTUATION, '['),
                    new Token(Token::STRING, 'foo'),
                    new Token(Token::PUNCTUATION, '=>'),
                    new Token(Token::STRING, 'bar'),
                    new Token(Token::PUNCTUATION, ','),
                    new Token(Token::PUNCTUATION, ']'),
                    new Token(Token::EOF)
                ))
            )
        );
    }

    /**
     * @dataProvider simpleArrayProvider
     */
    public function testArraySyntaxElements($stream)
    {
        $this->assertInstanceOf(
            '\\Modules\\Templating\\Compiler\\Nodes\\ArrayNode',
            $this->expressionParser->parse($stream)
        );
    }

    public function testParenthesisAfterIdentifierMakesAFunction()
    {
        $stream = new Stream(array(
            new Token(Token::IDENTIFIER, 'function'),
            new Token(Token::PUNCTUATION, '('),
            new Token(Token::PUNCTUATION, ')'),
            new Token(Token::EOF)
        ));
        /** @var $nodes FunctionNode */
        $nodes = $this->expressionParser->parse($stream);

        $this->assertInstanceof('\\Modules\\Templating\\Compiler\\Nodes\\FunctionNode', $nodes);

        $arguments = $nodes->getArguments();
        $this->assertEquals(0, count($arguments));
    }

    public function testArgumentsArePresentInFunctionNode()
    {
        $stream = new Stream(array(
            new Token(Token::IDENTIFIER, 'function'),
            new Token(Token::PUNCTUATION, '('),
            new Token(Token::STRING, 'foo'),
            new Token(Token::PUNCTUATION, ','),
            new Token(Token::STRING, 'bar'),
            new Token(Token::PUNCTUATION, ')'),
            new Token(Token::EOF)
        ));

        /** @var $nodes FunctionNode */
        $nodes = $this->expressionParser->parse($stream);

        $this->assertInstanceof('\\Modules\\Templating\\Compiler\\Nodes\\FunctionNode', $nodes);

        /** @var $arguments DataNode[] */
        $arguments = $nodes->getArguments();
        $this->assertEquals(2, count($arguments));
        $this->assertEquals('foo', $arguments[0]->getValue());
        $this->assertEquals('bar', $arguments[1]->getValue());
    }

    /**
     * @expectedException \Modules\Templating\Compiler\Exceptions\SyntaxException
     */
    public function testTrailingCommaIsNotAllowedInArgumentList()
    {
        $stream = new Stream(array(
            new Token(Token::IDENTIFIER, 'function'),
            new Token(Token::PUNCTUATION, '('),
            new Token(Token::STRING, 'foo'),
            new Token(Token::PUNCTUATION, ','),
            new Token(Token::PUNCTUATION, ')'),
            new Token(Token::EOF)
        ));

        $this->expressionParser->parse($stream);
    }

    public function testSimpleVariables()
    {
        $stream = new Stream(array(
            new Token(Token::VARIABLE, 'foo'),
            new Token(Token::EOF)
        ));

        /** @var $node VariableNode */
        $node = $this->expressionParser->parse($stream);

        $this->assertInstanceOf('\\Modules\\Templating\\Compiler\\Nodes\\VariableNode', $node);
        $this->assertEquals('foo', $node->getName());
    }

    public function testSquareBracketsMakeArrayIndexing()
    {
        $stream = new Stream(array(
            new Token(Token::VARIABLE, 'foo'),
            new Token(Token::PUNCTUATION, '['),
            new Token(Token::STRING, 'bar'),
            new Token(Token::PUNCTUATION, ']'),
            new Token(Token::EOF)
        ));

        /** @var $node ArrayIndexNode */
        $node = $this->expressionParser->parse($stream);

        /** @var $nameNode VariableNode */
        $nameNode = $this->getPropertyOfArrayIndexNode($node, 'identifier');
        /** @var $keyNode DataNode */
        $keyNode = $this->getPropertyOfArrayIndexNode($node, 'key');

        $this->assertInstanceOf('\\Modules\\Templating\\Compiler\\Nodes\\ArrayIndexNode', $node);

        $this->assertInstanceOf('\\Modules\\Templating\\Compiler\\Nodes\\VariableNode', $nameNode);
        $this->assertEquals('foo', $nameNode->getName());

        $this->assertInstanceOf('\\Modules\\Templating\\Compiler\\Nodes\\DataNode', $keyNode);
        $this->assertEquals('bar', $keyNode->getValue());
    }

    public function testChainedArrayAccessIsSupported()
    {
        $stream = new Stream(array(
            new Token(Token::VARIABLE, 'foo'),
            new Token(Token::PUNCTUATION, '['),
            new Token(Token::STRING, 'bar'),
            new Token(Token::PUNCTUATION, ']'),
            new Token(Token::PUNCTUATION, '['),
            new Token(Token::STRING, 'baz'),
            new Token(Token::PUNCTUATION, ']'),
            new Token(Token::EOF)
        ));

        /** @var $node ArrayIndexNode */
        $node = $this->expressionParser->parse($stream);
        $this->assertInstanceOf('\\Modules\\Templating\\Compiler\\Nodes\\ArrayIndexNode', $node);

        //secound access
        /** @var $identifierNode ArrayIndexNode */
        $identifierNode = $this->getPropertyOfArrayIndexNode($node, 'identifier');
        /** @var $keyNode DataNode */
        $keyNode = $this->getPropertyOfArrayIndexNode($node, 'key');

        $this->assertInstanceOf(
            '\\Modules\\Templating\\Compiler\\Nodes\\ArrayIndexNode',
            $identifierNode
        );
        $this->assertInstanceOf(
            '\\Modules\\Templating\\Compiler\\Nodes\\DataNode',
            $keyNode
        );
        $this->assertEquals('baz', $keyNode->getValue());

        //first access
        /** @var $firstIdentifierNode VariableNode */
        $firstIdentifierNode = $this->getPropertyOfArrayIndexNode($identifierNode, 'identifier');
        /** @var $firstKeyNode DataNode */
        $firstKeyNode = $this->getPropertyOfArrayIndexNode($identifierNode, 'key');

        $this->assertInstanceOf(
            '\\Modules\\Templating\\Compiler\\Nodes\\VariableNode',
            $firstIdentifierNode
        );
        $this->assertInstanceOf(
            '\\Modules\\Templating\\Compiler\\Nodes\\DataNode',
            $firstKeyNode
        );
        $this->assertEquals('foo', $firstIdentifierNode->getName());
        $this->assertEquals('bar', $firstKeyNode->getValue());
    }

    /**
     * @expectedException \Modules\Templating\Compiler\Exceptions\SyntaxException
     */
    public function testArrayIndexingRequiresAnIndex()
    {
        $stream = new Stream(array(
            new Token(Token::VARIABLE, 'foo'),
            new Token(Token::PUNCTUATION, '['),
            new Token(Token::PUNCTUATION, ']'),
            new Token(Token::EOF)
        ));

        $this->expressionParser->parse($stream);
    }

    /**
     * @param $node
     * @param $propertyName
     *
     * @return array
     */
    private function getPropertyOfArrayIndexNode(ArrayIndexNode $node, $propertyName)
    {
        $reflection = new \ReflectionClass($node);
        $property   = $reflection->getProperty($propertyName);
        $property->setAccessible(true);

        return $property->getValue($node);
    }
}
