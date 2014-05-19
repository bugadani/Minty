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

    public function parseArgumentList()
    {
        $arguments = array();

        if (!$this->stream->nextTokenIf(Token::PUNCTUATION, ')')) {
            while (!$this->stream->current()->test(Token::PUNCTUATION, ')')) {
                $arguments[] = $this->parseExpression(true);
                $this->stream->expectCurrent(Token::PUNCTUATION, array(',', ')'));
            }
        }

        return $arguments;
    }

    public function parsePostfixExpression($identifier)
    {
        if ($this->stream->nextTokenIf(Token::PUNCTUATION, '(')) {
            // function call
            $node = new FunctionNode($identifier, $this->parseArgumentList());

            $this->operandStack->push($node);

            return;
        }
        $operand = new IdentifierNode($identifier);
        if ($this->stream->nextTokenIf(Token::PUNCTUATION, '[')) {
            //array indexing
            $index = $this->parseExpression(true);

            $this->operandStack->push(new ArrayIndexNode($operand, $index));
        } else {
            $this->operandStack->push($operand);
        }
        // intentional
        if ($this->stream->nextTokenIf(Token::OPERATOR, $this->unaryPostfixTest)) {
            $token = $this->stream->current();

            $operator = $this->unaryPostfixOperators->getOperator($token->getValue());
            $this->popOperatorsCompared($operator);
            $operand = $this->operandStack->pop();

            $node = new OperatorNode($operator);
            $node->addOperand(OperatorNode::OPERAND_LEFT, $operand);

            $this->operandStack->push($node);
        }
    }

    public function parseArray()
    {
        //iterate over tokens
        $node = new ArrayNode();
        while (!$this->stream->current()->test(Token::PUNCTUATION, ']')) {
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

            $this->stream->expectCurrent(Token::PUNCTUATION, array(',', ']'));
        }
        //push array node to operand stack
        $this->operandStack->push($node);
    }

    public function parseToken()
    {
        do {
            $return = true;
            $token  = $this->stream->next();
            if ($token->test(Token::STRING) || $token->test(Token::LITERAL)) {
                $this->operandStack->push(new DataNode($token->getValue()));
            } elseif ($token->test(Token::IDENTIFIER)) {
                $this->parsePostfixExpression($token->getValue());
            } elseif ($token->test(Token::PUNCTUATION, '(')) {
                $this->parseExpression();
            } elseif ($token->test(Token::PUNCTUATION, '[')) {
                $this->parseArray();
            } elseif ($token->test(Token::OPERATOR, $this->unaryPrefixTest)) {
                $this->pushUnaryPrefixOperator($token);
                $return = false;
            } else {
                $type  = $token->getTypeString();
                $value = $token->getValue();
                $line  = $token->getLine();
                throw new SyntaxException("Unexpected {$type} ({$value}) token found in line {$line}");
            }
        } while (!$return);
    }

    /**
     * @param bool $return
     *
     * @return Node|null
     */
    public function parseExpression($return = false)
    {
        //push sentinel
        $this->operatorStack->push(null);

        $this->parseToken();
        while ($this->stream->next()->test(Token::OPERATOR, $this->binaryTest)) {
            $this->pushBinaryOperator($this->stream->current());
            $this->parseToken();
        }
        while ($this->operatorStack->top() !== null) {
            $this->popOperator();
        }
        //pop sentinel
        $this->operatorStack->pop();

        // A conditional is marked by '?' (punctuation, not operator) so it breaks the loop above.
        $this->parseConditional();

        if ($return) {
            return $this->operandStack->pop();
        }
    }

    public function parseConditional()
    {
        if (!$this->stream->current()->test(Token::PUNCTUATION, '?')) {
            return;
        }
        $node = new OperatorNode(new ConditionalOperator());
        $node->addOperand(OperatorNode::OPERAND_LEFT, $this->operandStack->pop());

        // Check whether the current expression is a simplified conditional expression (expr1 ?: expr3)
        if (!$this->stream->nextTokenIf(Token::PUNCTUATION, ':')) {
            $expression_two = $this->parseExpression(true);
            $node->addOperand(OperatorNode::OPERAND_MIDDLE, $expression_two);
        }

        $this->stream->expectCurrent(Token::PUNCTUATION, ':');
        $expression_three = $this->parseExpression(true);
        $node->addOperand(OperatorNode::OPERAND_RIGHT, $expression_three);

        $this->operandStack->push($node);
    }

    public function popOperatorsCompared(Operator $operator)
    {
        while ($this->compareToStackTop($operator)) {
            $this->popOperator();
        }
    }

    public function popOperator()
    {
        $operator = $this->operatorStack->pop();
        $node     = new OperatorNode($operator);
        $node->addOperand(OperatorNode::OPERAND_RIGHT, $this->operandStack->pop());
        if ($this->binaryOperators->exists($operator)) {
            $node->addOperand(OperatorNode::OPERAND_LEFT, $this->operandStack->pop());
        }
        $this->operandStack->push($node);
    }

    public function pushBinaryOperator(Token $token)
    {
        $operator = $this->binaryOperators->getOperator($token->getValue());
        $this->popOperatorsCompared($operator);
        $this->operatorStack->push($operator);
    }

    public function pushUnaryPrefixOperator(Token $token)
    {
        $operator = $this->unaryPrefixOperators->getOperator($token->getValue());
        $this->popOperatorsCompared($operator);
        $this->operatorStack->push($operator);
    }

    public function compareToStackTop(Operator $operator)
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
