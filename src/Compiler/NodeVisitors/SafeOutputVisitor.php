<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\NodeVisitors;

use Modules\Templating\Compiler\Node;
use Modules\Templating\Compiler\Nodes\DataNode;
use Modules\Templating\Compiler\Nodes\FunctionNode;
use Modules\Templating\Compiler\Nodes\IdentifierNode;
use Modules\Templating\Compiler\Nodes\OperatorNode;
use Modules\Templating\Compiler\Nodes\PrintNode;
use Modules\Templating\Compiler\Nodes\VariableNode;
use Modules\Templating\Compiler\NodeVisitor;
use Modules\Templating\Compiler\Operators\FilterOperator;
use Modules\Templating\Environment;
use Modules\Templating\iEnvironmentAware;

class SafeOutputVisitor extends NodeVisitor implements iEnvironmentAware
{
    /**
     * @var Environment
     */
    private $environment;
    private $inTag = false;
    private $variableAccessed = false;
    private $unsafeFunctionCalled = false;
    private $functionLevel = 0;
    private $autoescape;

    public function getPriority()
    {
        return 1;
    }

    public function setEnvironment(Environment $environment)
    {
        $this->autoescape  = $environment->getOption('autoescape', 1);
        $this->environment = $environment;
    }

    public function enterNode(Node $node)
    {
        if ($this->inTag) {
            if (!$this->autoescape) {
                return;
            }
            if ($this->isFilterOperator($node)) {
                if ($this->functionLevel++ === 0) {
                    $function = $this->environment->getFunction(
                        $node->getChild(OperatorNode::OPERAND_RIGHT)->getName()
                    );
                    $this->unsafeFunctionCalled |= !$function->getOption('is_safe');
                }
            } elseif ($node instanceof FunctionNode) {
                if ($this->functionLevel++ === 0) {
                    if ($node->getObject()) {
                        $this->unsafeFunctionCalled = true;
                    } else {
                        $function = $this->environment->getFunction($node->getName());
                        $this->unsafeFunctionCalled |= !$function->getOption('is_safe');
                    }
                }
            } elseif ($this->functionLevel === 0) {
                $this->variableAccessed |= $this->isUnsafeVariable($node);
            }
        } elseif ($this->isPrintNode($node)) {
            $this->inTag = true;
        }
    }

    public function leaveNode(Node $node)
    {
        if ($this->inTag) {
            if ($this->isPrintNode($node)) {
                if ($this->autoescape) {
                    $node->addData(
                        'is_safe',
                        !($this->variableAccessed || $this->unsafeFunctionCalled)
                    );
                    if ($this->autoescape === 1) {
                        $filterFor = new OperatorNode(
                            $this->environment->getBinaryOperators()->getOperator('.')
                        );
                        $filterFor->addChild(new VariableNode('_self'), OperatorNode::OPERAND_LEFT);
                        $filterFor->addChild(new IdentifierNode('extension'), OperatorNode::OPERAND_RIGHT);
                    } else {
                        $filterFor = new DataNode($this->autoescape);
                    }
                    $node->addChild($filterFor, 'filter_for');
                }
                $this->variableAccessed     = false;
                $this->unsafeFunctionCalled = false;
                $this->inTag                = false;
            } elseif ($node instanceof FunctionNode || $this->isFilterOperator($node)) {
                --$this->functionLevel;
            }
        }

        return true;
    }

    /**
     * @param Node $node
     *
     * @return bool
     */
    private function isPrintNode(Node $node)
    {
        return $node instanceof PrintNode;
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

    private function isUnsafeVariable(Node $node)
    {
        return $node instanceof VariableNode && $node->getName() !== '_self';
    }
}
