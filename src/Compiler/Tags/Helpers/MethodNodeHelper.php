<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Tags\Helpers;

use Minty\Compiler\Node;
use Minty\Compiler\Nodes\ExpressionNode;
use Minty\Compiler\Nodes\FunctionNode;
use Minty\Compiler\Nodes\IdentifierNode;
use Minty\Compiler\Nodes\TempVariableNode;
use Minty\Compiler\Nodes\VariableNode;
use Minty\Compiler\Parser;
use Minty\Compiler\Stream;

class MethodNodeHelper
{
    public function createContext($parse, Stream $stream, Parser $parser)
    {
        if ($parse) {
            $contextNode = new FunctionNode('createContext');
            $contextNode->addArgument($parser->parseExpression($stream));
            $contextNode->setObject(new TempVariableNode('environment'));
        } else {
            $contextNode = new TempVariableNode('context');
        }

        return $contextNode;
    }

    public function createMethodCallNode($object, $function, array $arguments = [])
    {
        $functionNode = new FunctionNode($function, $arguments);
        $functionNode->setObject($object);

        return $functionNode;
    }

    public function createRenderBlockNode($templateName, Node $contextNode)
    {
        return new ExpressionNode(
            $this->createMethodCallNode(
                new VariableNode('_self'),
                'renderBlock',
                [
                    new IdentifierNode($templateName),
                    $contextNode
                ]
            )
        );
    }

    public function createRenderFunctionNode($templateName, Node $contextNode)
    {
        return new ExpressionNode(
            $this->createMethodCallNode(
                new TempVariableNode('environment'),
                'render',
                [
                    $templateName,
                    $contextNode
                ]
            )
        );
    }
}
