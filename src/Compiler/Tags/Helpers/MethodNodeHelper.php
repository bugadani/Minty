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

class MethodNodeHelper
{
    public function createMethodCallNode($object, $function, array $arguments = array())
    {
        $functionNode = new FunctionNode($function, $arguments);
        $functionNode->setObject($object);

        return $functionNode;
    }

    public function createRenderBlockNode($templateName, Node $contextNode = null)
    {
        return new ExpressionNode(
            $this->createMethodCallNode(
            new VariableNode('_self'),
            'renderBlock',
            array(
                new IdentifierNode($templateName),
                $contextNode ? : new TempVariableNode('context')
            )
        ));
    }

    public function createRenderFunctionNode($templateName, $contextNode)
    {
        return new ExpressionNode(
            $this->createMethodCallNode(
                new TempVariableNode('environment'),
                'render',
                array(
                    $templateName,
                    $contextNode
                )
            )
        );
    }
}
