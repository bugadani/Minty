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
use Minty\iEnvironmentAware;

class EnvironmentVariableOptimizer extends NodeVisitor implements iEnvironmentAware
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
            if ($this->isEnvironmentVariable($node)) {
                $this->environmentAccessed = true;
            } elseif ($node instanceof FunctionNode) {
                if (!$node->getObject()) {
                    $this->environmentAccessed = $this->functionNeedsEnvironment($node);
                } elseif ($this->functionCallOnEnvironment($node)) {
                    $this->environmentAccessed = true;
                }
            } elseif ($this->isFilterOperator($node)) {
                $this->environmentAccessed = $this->functionNeedsEnvironment(
                    $node->getChild(OperatorNode::OPERAND_RIGHT)
                );
            }
        }
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

    /**
     * @param Node $node
     *
     * @return bool
     */
    private function isFilterOperator(Node $node)
    {
        return $node instanceof OperatorNode && $node->getOperator() instanceof FilterOperator;
    }

    /**
     * @param IdentifierNode $node
     *
     * @return bool
     */
    private function functionNeedsEnvironment(IdentifierNode $node)
    {
        $function = $this->environment->getFunction($node->getName());

        if ($function->getOption('needs_environment')) {
            return true;
        }

        $callback = $this->environment->getFunction($node->getName())->getCallback();
        if (!is_string($callback) || strpos($callback, ':') !== false) {
            return true;
        }

        return false;
    }

    /**
     * @param Node $node
     *
     * @return bool
     */
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

    /**
     * @param FunctionNode $node
     *
     * @return bool
     */
    private function functionCallOnEnvironment(FunctionNode $node)
    {
        if (!$node->getObject()) {
            return false;
        }

        return $this->isEnvironmentVariable($node->getObject());
    }

    /**
     * @param Node $node
     *
     * @return bool
     */
    private function isEnvironmentVariable(Node $node)
    {
        return $node instanceof TempVariableNode && $node->getName() === 'environment';
    }
}
