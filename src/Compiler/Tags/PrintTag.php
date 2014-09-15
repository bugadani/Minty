<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Tags;

use Minty\Compiler\Compiler;
use Minty\Compiler\Nodes\ExpressionNode;
use Minty\Compiler\Nodes\OperatorNode;
use Minty\Compiler\Nodes\TagNode;
use Minty\Compiler\Parser;
use Minty\Compiler\Stream;
use Minty\Compiler\Tag;
use Minty\Compiler\Token;

class PrintTag extends Tag
{

    public function getTag()
    {
        return 'print';
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $expression = $parser->parseExpression($stream);
        if ($stream->current()->test(Token::PUNCTUATION, ':')) {

            $varNode = new OperatorNode(
                $parser->getEnvironment()->getBinaryOperators()->getOperator(':')
            );
            $varNode->addChild($expression, OperatorNode::OPERAND_LEFT);
            $varNode->addChild($parser->parseExpression($stream), OperatorNode::OPERAND_RIGHT);

            $node = new ExpressionNode($varNode);
        } else {
            $node = new TagNode($this);
            $node->addChild($expression, 'expression');
        }

        return $node;
    }

    public function compile(Compiler $compiler, TagNode $node)
    {
        $compiler
            ->indented('echo ')
            ->compileNode($node->getChild('expression'))
            ->add(';');
    }
}
