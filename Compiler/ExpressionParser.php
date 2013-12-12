<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler;

use Modules\Templating\Compiler\Exceptions\SyntaxException;
use Modules\Templating\Compiler\Nodes\ArrayIndexNode;
use Modules\Templating\Compiler\Nodes\ArrayNode;
use Modules\Templating\Compiler\Nodes\DataNode;
use Modules\Templating\Compiler\Nodes\FunctionNode;
use Modules\Templating\Compiler\Nodes\IdentifierNode;
use Modules\Templating\Compiler\Nodes\OperatorNode;

/**
 * Expression parser is based on the Shunting Yard algorithm by Edsger W. Dijkstra
 * @link http://www.engr.mun.ca/~theo/Misc/exp_parsing.htm
 */
class ExpressionParser
{
    /**
     * @var Operator[]
     */
    private $operator_stack;

    /**
     * @var Node[]
     */
    private $operand_stack;

    /**
     * @var string[]
     */
    private $binary_operators;

    /**
     * @var string[]
     */
    private $unary_prefix_operators;

    /**
     * @var string[]
     */
    private $unary_postfix_operators;

    /**
     * @var Stream
     */
    private $stream;

    public function __construct(Environment $environment)
    {
        $this->binary_operators        = $environment->getBinaryOperatorSigns();
        $this->unary_prefix_operators  = $environment->getPrefixUnaryOperatorSigns();
        $this->unary_postfix_operators = $environment->getPostfixUnaryOperatorSigns();
    }

    public function parse(Stream $stream)
    {
        $this->operator_stack = array();
        $this->operand_stack  = array();
        $this->stream         = $stream;

        $this->parseExpression();

        return end($this->operand_stack);
    }

    public function parseArgumentList()
    {
        $arguments = array();

        $first = true;
        if (!$this->stream->nextTokenIf(Token::PUNCTUATION, ')')) {
            while (!$this->stream->current()->test(Token::PUNCTUATION, ')')) {
                if ($first) {
                    $first = false;
                } else {
                    $this->stream->step(-1);
                    $this->stream->expect(Token::PUNCTUATION, ',');
                }
                $this->parseExpression();
                $arguments[] = array_pop($this->operand_stack);
            }
        }
        $this->operand_stack[] = $arguments;
    }

    public function parsePostfixExpression()
    {
        $node = array_pop($this->operand_stack);
        if ($this->stream->nextTokenIf(Token::PUNCTUATION, '(')) {
            //fn call
            $this->parseArgumentList();
            $arguments = array_pop($this->operand_stack);
            $node      = new FunctionNode($node);
            $node->addArguments($arguments);
        } elseif ($this->stream->nextTokenIf(Token::PUNCTUATION, '[')) {
            //array indexing
            $this->parseExpression();
            $index = array_pop($this->operand_stack);
            $node  = new ArrayIndexNode($node, $index);
        } elseif ($this->stream->nextTokenIf(Token::OPERATOR)) {
            $token = $this->stream->current();
            if ($this->isUnaryPostfix($token)) {
                $operator = $this->getUnaryPostfixOperator($token);
                $operand  = $node;
                $node     = new OperatorNode($operator);
                $node->addOperand(OperatorNode::OPERAND_RIGHT, $operand);
            } else {
                $this->stream->step(-1);
            }
        }
        $this->operand_stack[] = $node;
    }

    public function parseDataToken(Token $token)
    {
        switch ($token->getType()) {
            case Token::STRING:
            case Token::LITERAL:
                $this->operand_stack[] = new DataNode($token->getValue());
                break;
            case Token::IDENTIFIER:
                //identifier - handle function calls and array indexing
                $this->operand_stack[] = new IdentifierNode($token->getValue());
                $this->parsePostfixExpression();
                break;
        }
    }

    public function parseArray()
    {
        //iterate over tokens
        $next = $this->stream->next();
        $node = new ArrayNode();
        while (!$next->test(Token::PUNCTUATION, ']')) {
            //expressions are allowed as both array keys and values.
            if ($next->test(Token::PUNCTUATION, '(')) {
                $this->parseExpression();
            } elseif ($next->isDataType()) {
                $this->parseDataToken($next);
            }

            $next = $this->stream->next();
            if ($next->test(Token::PUNCTUATION, ':')) {
                //the previous value was a key
                $key  = array_pop($this->operand_stack);
                $this->parseExpression();
                $next = $this->stream->current();
            } else {
                $key = null;
            }
            $value = array_pop($this->operand_stack);
            $node->add($value, $key);

            if ($next->test(Token::PUNCTUATION, ',')) {
                $next = $this->stream->next();
            } elseif (!$next->test(Token::PUNCTUATION, ']')) {
                $string  = 'Unexpected %s (%s) token found in line %s';
                $message = sprintf($string, $next->getTypeString(), $next->getValue(), $next->getLine());
                throw new SyntaxException($message);
            }
        }
        //push array node to operand stack
        $this->operand_stack[] = $node;
    }

    public function parseToken()
    {
        $token = $this->stream->current();
        if ($token->isDataType()) {
            $this->parseDataToken($token);
        } elseif ($token->test(Token::PUNCTUATION, '(')) {
            $this->parseExpression();
        } elseif ($token->test(Token::PUNCTUATION, '[')) {
            $this->parseArray();
        } elseif ($this->isUnaryPrefix($token)) {
            $this->pushUnaryPrefixOperator($token);
            $this->stream->next();
            $this->parseToken();
        } else {
            $string    = 'Unexpected %s (%s) token found in line %d';
            $exception = sprintf($string, $token->getTypeString(), $token->getValue(), $token->getLine());
            throw new SyntaxException($exception);
        }
    }

    public function parseExpression()
    {
        //push sentinel
        $this->operator_stack[] = null;

        $this->stream->next();

        $this->parseToken();
        $next = $this->stream->next();
        while ($this->isBinary($next)) {
            //?: can be handled here?
            $this->pushBinaryOperator($next);
            $this->stream->next();
            $this->parseToken();
            $next = $this->stream->next();
        }
        while (end($this->operator_stack) !== null) {
            $this->popOperator();
        }
        //pop sentinel
        array_pop($this->operator_stack);
    }

    public function popOperator()
    {
        $operator = array_pop($this->operator_stack);
        if ($this->isOperatorBinary($operator)) {
            $right = array_pop($this->operand_stack);
            $left  = array_pop($this->operand_stack);
            $node  = $this->makeBinaryNode($operator, $left, $right);
        } else {
            $operand = array_pop($this->operand_stack);
            $node    = $this->makeUnaryNode($operator, $operand);
        }
        array_push($this->operand_stack, $node);
    }

    private function getBinaryOperator(Token $token)
    {
        return $this->binary_operators[$token->getValue()];
    }

    private function getUnaryPrefixOperator(Token $token)
    {
        return $this->unary_prefix_operators[$token->getValue()];
    }

    private function getUnaryPostfixOperator(Token $token)
    {
        return $this->unary_postfix_operators[$token->getValue()];
    }

    public function pushBinaryOperator(Token $token)
    {
        $operator = $this->getBinaryOperator($token);
        while ($this->compareToStackTop($operator)) {
            $this->popOperator();
        }
        $this->operator_stack[] = $operator;
    }

    public function pushUnaryPrefixOperator(Token $token)
    {
        $operator = $this->getUnaryPrefixOperator($token);
        while ($this->compareToStackTop($operator)) {
            $this->popOperator();
        }
        $this->operator_stack[] = $operator;
    }

    public function makeUnaryNode(Operator $operator, $operand)
    {
        $node = new OperatorNode($operator);
        $node->addOperand(OperatorNode::OPERAND_LEFT, $operand);
        return $node;
    }

    public function makeBinaryNode(Operator $operator, $operand_left, $operand_right)
    {
        $node = new OperatorNode($operator);
        $node->addOperand(OperatorNode::OPERAND_LEFT, $operand_left);
        $node->addOperand(OperatorNode::OPERAND_RIGHT, $operand_right);
        return $node;
    }

    public function compareToStackTop(Operator $operator)
    {
        $top = end($this->operator_stack);
        if ($this->isOperatorBinary($operator) && $operator === $top) {
            if ($operator->isAssociativity(Operator::LEFT)) {
                return 1;
            } elseif ($operator->isAssociativity(Operator::RIGHT)) {
                return 0;
            } else {
                $symbols = $operator->operators();
                if (is_array($symbols)) {
                    $symbols = implode(', ', $symbols);
                }
                $message = sprintf('Binary operator %s is not associative.', $symbols);
                throw new ParseException($message);
            }
        }
        return $this->precedence($top) >= $this->precedence($operator);
    }

    public function precedence(Operator $operator = null)
    {
        if ($operator === null) {
            return -1;
        }
        return $operator->getPrecedence();
    }

    private function isOperatorBinary(Operator $operator)
    {
        return in_array($operator, $this->binary_operators, true);
    }

    private function isBinary(Token $token)
    {
        return $token->test(Token::OPERATOR) && isset($this->binary_operators[$token->getValue()]);
    }

    private function isUnaryPrefix(Token $token)
    {
        return $token->test(Token::OPERATOR) && isset($this->unary_prefix_operators[$token->getValue()]);
    }

    private function isUnaryPostfix(Token $token)
    {
        return $token->test(Token::OPERATOR) && isset($this->unary_postfix_operators[$token->getValue()]);
    }
}
