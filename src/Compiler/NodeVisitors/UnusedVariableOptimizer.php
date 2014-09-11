<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\NodeVisitors;

use Minty\Compiler\Node;
use Minty\Compiler\Nodes\ClassNode;
use Minty\Compiler\Nodes\FunctionNode;
use Minty\Compiler\Nodes\IdentifierNode;
use Minty\Compiler\Nodes\OperatorNode;
use Minty\Compiler\Nodes\RootNode;
use Minty\Compiler\Nodes\TempVariableNode;
use Minty\Compiler\NodeVisitor;
use Minty\Compiler\Operators\FilterOperator;
use Minty\Environment;
use Minty\EnvironmentAwareInterface;

class UnusedVariableOptimizer extends NodeVisitor implements EnvironmentAwareInterface
{
    /**
     * @var ClassNode
     */
    private $currentClassNode;

    /**
     * @var RootNode
     */
    private $currentBlock;

    /**
     * @var bool
     */
    private $environmentAccessed;

    /**
     * @var Environment
     */
    private $environment;

    public function getPriority()
    {
        return 2;
    }

    public function setEnvironment(Environment $environment)
    {
        $this->environment = $environment;
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof ClassNode) {
            $this->currentClassNode = $node;
        } elseif ($this->isBlockRoot($node)) {
            $this->currentBlock        = $node;
            $this->environmentAccessed = false;
        } elseif (isset($this->currentBlock) && !$this->environmentAccessed) {
            $this->environmentAccessed = $this->checkForEnvironmentAccess($node);
        }
    }

    /**
     * @param Node $node
     *
     * @return bool
     */
    private function checkForEnvironmentAccess(Node $node)
    {
        if ($this->isEnvironmentVariable($node)) {
            return true;
        }
        if ($node instanceof FunctionNode) {
            if ($node->getObject()) {
                return $this->checkForEnvironmentObject($node->getObject());
            }

            $function = $node;
        } elseif ($this->isFilterOperator($node)) {
            $function = $node->getChild(OperatorNode::OPERAND_RIGHT);
        } else {
            return false;
        }

        return $this->functionNeedsEnvironment($function);
    }

    public function leaveNode(Node $node)
    {
        if ($this->isBlockRoot($node)) {
            $this->currentBlock->addData('environment_accessed', $this->environmentAccessed);
            unset($this->currentBlock);
        } elseif ($node instanceof ClassNode) {
            unset($this->currentClassNode);
        }

        return true;
    }

    private function isFilterOperator(Node $node)
    {
        return $node instanceof OperatorNode && $node->getOperator() instanceof FilterOperator;
    }

    private function functionNeedsEnvironment(IdentifierNode $node)
    {
        $function = $this->environment->getFunction($node->getData('name'));

        if ($function->getOption('needs_environment')) {
            return true;
        }

        $callback = $function->getCallback();

        //this is the condition FunctionCompiler uses to check if a function can be directly compiled
        if (!is_string($callback) || strpos($callback, ':') !== false) {
            return true;
        }

        return false;
    }

    private function isBlockRoot(Node $node)
    {
        if (!isset($this->currentClassNode)) {
            return false;
        }
        if (!$node instanceof RootNode) {
            return false;
        }

        return in_array($node, $this->currentClassNode->getChildren(), true);
    }

    private function isEnvironmentVariable(Node $node)
    {
        return $node instanceof TempVariableNode && $node->getData('name') === 'environment';
    }

    private function checkForEnvironmentObject(Node $node)
    {
        while ($node instanceof FunctionNode) {
            $node = $node->getObject();
        }

        if(!$node) {
            return false;
        }

        return $this->isEnvironmentVariable($node);
    }
}
