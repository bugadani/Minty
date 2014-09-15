<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Tags;

use Minty\Compiler\Node;
use Minty\Compiler\Nodes\ExpressionNode;
use Minty\Compiler\Nodes\OperatorNode;
use Minty\Compiler\Nodes\RootNode;
use Minty\Compiler\Operators\PropertyAccessOperator;
use Minty\Compiler\Parser;
use Minty\Compiler\Stream;
use Minty\Compiler\Tag;
use Minty\Compiler\Token;

class SetTag extends Tag
{

    public function getTag()
    {
        return 'set';
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $node = new RootNode();

        do {
            $left = $parser->parseExpression($stream);
            $stream->expectCurrent(Token::PUNCTUATION, ':');
            $right = $parser->parseExpression($stream);

            if ($this->isPropertyAccessOperator($left)) {
                $left->addData('mode', 'set');
                $left->addChild($right);
                $varNode = $left;
            } else {
                $varNode = new OperatorNode(
                    $parser->getEnvironment()->getBinaryOperators()->getOperator(':')
                );
                $varNode->addChild($left, OperatorNode::OPERAND_LEFT);
                $varNode->addChild($right, OperatorNode::OPERAND_RIGHT);
            }
            $node->addChild(new ExpressionNode($varNode));
        } while ($stream->current()->test(Token::PUNCTUATION, ','));

        return $node;
    }

    /**
     * @param Node $operand
     *
     * @return bool
     */
    private function isPropertyAccessOperator(Node $operand)
    {
        if (!$operand instanceof OperatorNode) {
            return false;
        }

        return $operand->getOperator() instanceof PropertyAccessOperator;
    }
}
