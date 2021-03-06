<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler;

use Minty\Compiler\Exceptions\ParseException;
use Minty\Compiler\Exceptions\SyntaxException;
use Minty\Compiler\Nodes\ArrayIndexNode;
use Minty\Compiler\Nodes\ArrayNode;
use Minty\Compiler\Nodes\DataNode;
use Minty\Compiler\Nodes\FunctionNode;
use Minty\Compiler\Nodes\IdentifierNode;
use Minty\Compiler\Nodes\VariableNode;
use Minty\Compiler\Operators\ConditionalOperator;
use Minty\Compiler\Operators\PropertyAccessOperator;
use Minty\Environment;

/**
 * Expression parser is based on the Shunting Yard algorithm by Edsger W. Dijkstra
 *
 * @link http://www.engr.mun.ca/~theo/Misc/exp_parsing.htm
 */
class ExpressionParser
{
    /**
     * @var \SplStack
     */
    private $operatorStack;

    /**
     * @var \SplStack
     */
    private $operandStack;

    /**
     * @var Stream
     */
    private $stream;

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
    }

    /**
     * @param Stream $stream
     *
     * @return Node|null
     */
    public function parse(Stream $stream)
    {
        $this->operatorStack = new \SplStack();
        $this->operandStack  = new \SplStack();
        $this->stream        = $stream;

        return $this->parseExpression(true);
    }

    private function parseArgumentList()
    {
        $arguments = [];

        if (!$this->stream->nextTokenIf(Token::PUNCTUATION, ')')) {
            $token = $this->stream->current();
            while (!$token->test(Token::PUNCTUATION, ')')) {
                $arguments[] = $this->parseExpression(true);
                $token       = $this->stream->expectCurrent(Token::PUNCTUATION, [',', ')']);
            }
        }

        return $arguments;
    }

    private function parseIdentifier($identifier)
    {
        if ($this->stream->nextTokenIf(Token::PUNCTUATION, '(')) {
            //function call
            $node = new FunctionNode(
                $identifier,
                $this->parseArgumentList()
            );

            $lastOperator = $this->operatorStack->top();
            if ($lastOperator instanceof PropertyAccessOperator) {
                $this->operatorStack->pop();
                $node->setObject($this->operandStack->pop());
            }
        } else {
            $node = new IdentifierNode($identifier);
        }
        $this->operandStack->push($node);
    }

    private function parseVariable($variable)
    {
        $operand = new VariableNode($variable);
        while ($this->stream->nextTokenIf(Token::PUNCTUATION, '[')) {
            //array indexing
            $operand = new ArrayIndexNode(
                $operand,
                $this->parseExpression(true)
            );
        }
        $this->operandStack->push($operand);
    }

    private function parsePostfixOperator()
    {
        $token = $this->stream->next();
        if ($token->test(Token::OPERATOR, [$this->unaryPostfixOperators, 'isOperator'])) {
            $operator = $this->unaryPostfixOperators->getOperator($token->getValue());
            while ($this->compareToStackTop($operator)) {
                $this->popOperator();
            }

            $this->operandStack->push(
                $operator->createNode(
                    $this->operandStack->pop()
                )
            );
            $token = $this->stream->next();
        }

        return $token;
    }

    private function parseArray()
    {
        $node  = new ArrayNode();
        $token = $this->stream->current();

        //iterate over tokens
        while (!$token->test(Token::PUNCTUATION, ']')) {
            //Checking here allows for a trailing comma.
            if ($this->stream->nextTokenIf(Token::PUNCTUATION, ']')) {
                break;
            }
            //expressions are allowed as both array keys and values.
            $value = $this->parseExpression(true);

            if ($this->stream->current()->test(Token::PUNCTUATION, [':', '=>'])) {
                //the previous value was a key
                $key   = $value;
                $value = $this->parseExpression(true);
            } else {
                $key = null;
            }
            $node->add($value, $key);

            $token = $this->stream->expectCurrent(Token::PUNCTUATION, [',', ']']);
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

            switch ($type) {
                case Token::STRING:
                case Token::LITERAL:
                    $this->operandStack->push(new DataNode($value));
                    break;

                case Token::IDENTIFIER:
                    $this->parseIdentifier($value);
                    break;

                case Token::VARIABLE:
                    $this->parseVariable($value);
                    break;

                case Token::PUNCTUATION:
                    switch ($value) {
                        case '(':
                            $this->parseExpression(false);
                            break;

                        case '[':
                            $this->parseArray();
                            break;

                        default:
                            $type = $token->getTypeString();
                            $line = $token->getLine();
                            throw new SyntaxException("Unexpected {$type} ({$value}) token", $line);
                    }
                    break;

                default:
                    $this->stream->expectCurrent(
                        Token::OPERATOR,
                        [$this->unaryPrefixOperators, 'isOperator']
                    );
                    $this->pushOperator(
                        $this->unaryPrefixOperators->getOperator($value)
                    );
                    $done = false;
                    break;
            }
        } while (!$done);

        return $this->parsePostfixOperator();
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

        while ($token->test(Token::OPERATOR, [$this->binaryOperators, 'isOperator'])) {
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

        //A conditional is marked by '?' (punctuation, not operator) so it breaks the loop above.
        if ($token->test(Token::PUNCTUATION, '?')) {
            $this->parseConditional();
        }

        if ($return) {
            return $this->operandStack->pop();
        }

        return null;
    }

    private function parseConditional()
    {
        //Only instantiate ConditionalOperator when there is a possibility of it being used
        if (!isset($this->conditionalOperator)) {
            $this->conditionalOperator = new ConditionalOperator();
        }
        $left = $this->operandStack->pop();

        // Check whether the current expression is a simplified conditional
        // expression (expr1 ?: expr3)
        if (!$this->stream->nextTokenIf(Token::PUNCTUATION, ':')) {
            $middle = $this->parseExpression(true);
            $this->stream->expectCurrent(Token::PUNCTUATION, ':');
        } else {
            $middle = null;
        }

        $right = $this->parseExpression(true);

        $this->operandStack->push(
            $this->conditionalOperator->createNode($left, $right, $middle)
        );
    }

    private function popOperator()
    {
        $operator = $this->operatorStack->pop();
        $right    = $this->operandStack->pop();
        if ($this->binaryOperators->exists($operator)) {
            $operatorNode = $operator->createNode(
                $this->operandStack->pop(),
                $right
            );
        } else {
            $operatorNode = $operator->createNode(
                null,
                $right
            );
        }
        $this->operandStack->push($operatorNode);
    }

    private function pushOperator(Operator $operator)
    {
        while ($this->compareToStackTop($operator)) {
            $this->popOperator();
        }
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

            //e.g. (5 is divisible by 2 is divisible by 3) is not considered valid
            $symbols = $operator->operators();
            if (is_array($symbols)) {
                $symbols = implode(', ', $symbols);
            }
            throw new ParseException(
                "Binary operator '{$symbols}' is not associative",
                $this->stream->current()->getLine()
            );
        }

        return $top->getPrecedence() >= $operator->getPrecedence();
    }
}
