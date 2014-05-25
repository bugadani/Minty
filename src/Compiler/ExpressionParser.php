<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler;

use Modules\Templating\Compiler\Exceptions\ParseException;
use Modules\Templating\Compiler\Exceptions\SyntaxException;
use Modules\Templating\Compiler\Nodes\ArrayIndexNode;
use Modules\Templating\Compiler\Nodes\ArrayNode;
use Modules\Templating\Compiler\Nodes\DataNode;
use Modules\Templating\Compiler\Nodes\FunctionNode;
use Modules\Templating\Compiler\Nodes\IdentifierNode;
use Modules\Templating\Compiler\Nodes\OperatorNode;
use Modules\Templating\Compiler\Operators\ConditionalOperator;
use Modules\Templating\Environment;
use SplStack;

/**
 * Expression parser is based on the Shunting Yard algorithm by Edsger W. Dijkstra
 *
 * @link http://www.engr.mun.ca/~theo/Misc/exp_parsing.htm
 */
class ExpressionParser
{
    /**
     * @var SplStack
     */
    private $operatorStack;

    /**
     * @var SplStack
     */
    private $operandStack;

    /**
     * @var Stream
     */
    private $stream;

    /**
     * @var callable
     */
    private $binaryTest;

    /**
     * @var callable
     */
    private $unaryPostfixTest;

    /**
     * @var callable
     */
    private $unaryPrefixTest;

    /**
     * @var OperatorCollection
     */
    private $binaryOperators;

    /**
     * @var OperatorCollection
     */
    private $unaryPrefixOperators;

    /**
     * @var OperatorCollection
     */
    private $unaryPostfixOperators;

    /**
     * @var ConditionalOperator
     */
    private $conditionalOperator;

    public function __construct(Environment $environment)
    {
        $this->binaryOperators       = $environment->getBinaryOperators();
        $this->unaryPrefixOperators  = $environment->getUnaryPrefixOperators();
        $this->unaryPostfixOperators = $environment->getUnaryPostfixOperators();

        $this->binaryTest       = array($this->binaryOperators, 'isOperator');
        $this->unaryPrefixTest  = array($this->unaryPrefixOperators, 'isOperator');
        $this->unaryPostfixTest = array($this->unaryPostfixOperators, 'isOperator');
    }

    /**
     * @param Stream $stream
     *
     * @return Node|null
     */
    public function parse(Stream $stream)
    {
        $this->operatorStack = new SplStack();
        $this->operandStack  = new SplStack();
        $this->stream        = $stream;

        return $this->parseExpression(true);
    }

    private function parseArgumentList()
    {
        $arguments = array();

        if (!$this->stream->nextTokenIf(Token::PUNCTUATION, ')')) {
            $token = $this->stream->current();
            while (!$token->test(Token::PUNCTUATION, ')')) {
                $arguments[] = $this->parseExpression(true);
                $token       = $this->stream->expectCurrent(Token::PUNCTUATION, array(',', ')'));
            }
        }

        return $arguments;
    }

    private function parsePostfixExpression($identifier)
    {
        if ($this->stream->nextTokenIf(Token::PUNCTUATION, '(')) {
            // function call
            $this->operandStack->push(
                new FunctionNode(
                    $identifier,
                    $this->parseArgumentList()
                )
            );

            return;
        }
        $operand = new IdentifierNode($identifier);
        while ($this->stream->nextTokenIf(Token::PUNCTUATION, '[')) {
            //array indexing
            $operand = new ArrayIndexNode(
                $operand,
                $this->parseExpression(true)
            );
        }
        $this->operandStack->push($operand);

        if ($token = $this->stream->nextTokenIf(Token::OPERATOR, $this->unaryPostfixTest)) {
            $operator = $this->unaryPostfixOperators->getOperator($token->getValue());
            $this->popOperatorsCompared($operator);

            $node = new OperatorNode($operator);
            $node->addOperand(
                OperatorNode::OPERAND_LEFT,
                $this->operandStack->pop()
            );

            $this->operandStack->push($node);
        }
    }

    private function parseArray()
    {
        //iterate over tokens
        $node  = new ArrayNode();
        $token = $this->stream->current();

        while (!$token->test(Token::PUNCTUATION, ']')) {
            // Checking here allows for a trailing comma.
            if ($this->stream->nextTokenIf(Token::PUNCTUATION, ']')) {
                break;
            }
            //expressions are allowed as both array keys and values.
            $value = $this->parseExpression(true);

            if ($this->stream->current()->test(Token::PUNCTUATION, array(':', '=>'))) {
                //the previous value was a key
                $key   = $value;
                $value = $this->parseExpression(true);
            } else {
                $key = null;
            }
            $node->add($value, $key);

            $token = $this->stream->expectCurrent(Token::PUNCTUATION, array(',', ']'));
        }
        //push array node to operand stack
        $this->operandStack->push($node);
    }

    /**
     * @return Token The next token
     * @throws Exceptions\SyntaxException
     */
    private function parseToken()
    {
        do {
            $done  = true;
            $token = $this->stream->next();

            $type  = $token->getType();
            $value = $token->getValue();

            $unexpectedToken = false;
            switch ($type) {
                case Token::STRING:
                case Token::LITERAL:
                    $this->operandStack->push(new DataNode($value));
                    break;

                case Token::IDENTIFIER:
                    $this->parsePostfixExpression($value);
                    break;

                case Token::PUNCTUATION:
                    switch ($value) {
                        case '(':
                            $this->parseExpression();
                            break;

                        case '[':
                            $this->parseArray();
                            break;

                        default:
                            $unexpectedToken = true;
                            break;
                    }
                    break;

                default:
                    if ($token->test(Token::OPERATOR, $this->unaryPrefixTest)) {
                        $this->pushOperator(
                            $this->unaryPrefixOperators->getOperator($value)
                        );
                        $done = false;
                    } else {
                        $unexpectedToken = true;
                    }
                    break;
            }
            if ($unexpectedToken) {
                $type = $token->getTypeString();
                throw new SyntaxException("Unexpected {$type} ({$value}) token", $token->getLine());
            }
        } while (!$done);

        return $this->stream->next();
    }

    /**
     * @param bool $return
     *
     * @return Node|null
     */
    private function parseExpression($return = false)
    {
        //push sentinel
        $this->operatorStack->push(null);

        $token = $this->parseToken();
        while ($token->test(Token::OPERATOR, $this->binaryTest)) {
            $this->pushOperator(
                $this->binaryOperators->getOperator($token->getValue())
            );
            $token = $this->parseToken();
        }
        while ($this->operatorStack->top() !== null) {
            $this->popOperator();
        }
        //pop sentinel
        $this->operatorStack->pop();

        // A conditional is marked by '?' (punctuation, not operator) so it breaks the loop above.
        if ($token->test(Token::PUNCTUATION, '?')) {
            $this->parseConditional();
        }

        if ($return) {
            return $this->operandStack->pop();
        }

        return null;
    }

    /**
     * @return ConditionalOperator
     */
    private function getConditionalOperator()
    {
        if (!isset($this->conditionalOperator)) {
            $this->conditionalOperator = new ConditionalOperator();
        }

        return $this->conditionalOperator;
    }

    private function parseConditional()
    {
        $node = new OperatorNode($this->getConditionalOperator());
        $node->addOperand(
            OperatorNode::OPERAND_LEFT,
            $this->operandStack->pop()
        );

        // Check whether the current expression is a simplified conditional expression (expr1 ?: expr3)
        if (!$this->stream->nextTokenIf(Token::PUNCTUATION, ':')) {
            $node->addOperand(
                OperatorNode::OPERAND_MIDDLE,
                $this->parseExpression(true)
            );
            $this->stream->expectCurrent(Token::PUNCTUATION, ':');
        }

        $node->addOperand(
            OperatorNode::OPERAND_RIGHT,
            $this->parseExpression(true)
        );

        $this->operandStack->push($node);
    }

    private function popOperatorsCompared(Operator $operator)
    {
        while ($this->compareToStackTop($operator)) {
            $this->popOperator();
        }
    }

    private function popOperator()
    {
        $operator = $this->operatorStack->pop();
        $node     = new OperatorNode($operator);
        $node->addOperand(
            OperatorNode::OPERAND_RIGHT,
            $this->operandStack->pop()
        );
        if ($this->binaryOperators->exists($operator)) {
            $node->addOperand(
                OperatorNode::OPERAND_LEFT,
                $this->operandStack->pop()
            );
        }
        $this->operandStack->push($node);
    }

    private function pushOperator(Operator $operator)
    {
        $this->popOperatorsCompared($operator);
        $this->operatorStack->push($operator);
    }

    private function compareToStackTop(Operator $operator)
    {
        $top = $this->operatorStack->top();
        if ($top === null) {
            return false;
        }
        if ($this->binaryOperators->exists($operator) && $operator === $top) {
            if ($operator->isAssociativity(Operator::LEFT)) {
                return true;
            }
            if ($operator->isAssociativity(Operator::RIGHT)) {
                return false;
            }

            $symbols = $operator->operators();
            if (is_array($symbols)) {
                $symbols = implode(', ', $symbols);
            }
            throw new ParseException("Binary operator {$symbols} is not associative.");
        }

        return $top->getPrecedence() >= $operator->getPrecedence();
    }
}
