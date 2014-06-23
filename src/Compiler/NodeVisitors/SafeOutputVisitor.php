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
use Minty\Compiler\Nodes\DataNode;
use Minty\Compiler\Nodes\FunctionNode;
use Minty\Compiler\Nodes\OperatorNode;
use Minty\Compiler\Nodes\TagNode;
use Minty\Compiler\Nodes\VariableNode;
use Minty\Compiler\NodeVisitor;
use Minty\Compiler\Operators\FilterOperator;
use Minty\Compiler\Tags\AutofilterTag;
use Minty\Compiler\Tags\PrintTag;
use Minty\Environment;
use Minty\iEnvironmentAware;

class SafeOutputVisitor extends NodeVisitor implements iEnvironmentAware
{
    /**
     * @var Environment
     */
    private $environment;
    private $inTag = false;
    private $functionLevel = 0;
    private $isSafe;
    private $autofilter;
    private $autofilterStack = array();
    private $defaultAutofilterStrategy;
    private $extension;

    public function getPriority()
    {
        return 1;
    }

    public function setEnvironment(Environment $environment)
    {
        $this->environment               = $environment;
        $this->autofilter = $environment->getOption('autofilter');
        $this->defaultAutofilterStrategy = $environment->getOption('default_autofilter_strategy');
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof ClassNode) {
            $template = $node->getTemplateName();
            if ($this->autofilter === 1) {
                $dot = strrpos($template, '.');
                if ($dot !== false) {
                    $this->extension = substr($template, $dot + 1);
                } else {
                    $this->extension = $this->defaultAutofilterStrategy;
                }
                $this->autofilterStack[] = $this->autofilter;
                $this->autofilter        = $this->extension;
            }
        } elseif ($this->inTag) {
            if (!$this->autofilter) {
                return;
            }
            if ($this->isFilterOperator($node)) {
                $this->isSafe &= $this->isFilterSafe($node);
            } elseif ($node instanceof FunctionNode) {
                $this->isSafe &= $this->isFunctionNodeSafe($node);
            } elseif ($this->functionLevel === 0) {
                if ($this->isUnsafeVariable($node)) {
                    $this->isSafe = false;
                }
            }
        } elseif ($this->isPrintNode($node)) {
            $this->inTag  = true;
            $this->isSafe = true;
        } elseif ($this->isAutofilterTag($node)) {
            $this->autofilterStack[] = $this->autofilter;
            $strategy                = $node->getData('strategy');
            $this->autofilter        = $strategy === 1 ? $this->extension : $strategy;
        }
    }

    public function leaveNode(Node $node)
    {
        if ($this->inTag) {
            if ($this->isPrintNode($node)) {
                $node->addData('is_safe', !$this->autofilter || $this->isSafe);
                if ($this->autofilter) {
                    $node->addChild(new DataNode($this->autofilter), 'filter_for');
                }
                $this->inTag = false;
            } elseif ($node instanceof FunctionNode || $this->isFilterOperator($node)) {
                --$this->functionLevel;
            }
        } elseif ($this->isAutofilterTag($node) || $node instanceof ClassNode) {
            $this->autofilter = array_pop($this->autofilterStack);
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
        return $node instanceof TagNode && $node->getTag() instanceof PrintTag;
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

    private function isAutofilterTag(Node $node)
    {
        return $node instanceof TagNode && $node->getTag() instanceof AutofilterTag;
    }

    /**
     * @param FunctionNode $node
     *
     * @return bool
     */
    private function isFunctionNodeSafe(FunctionNode $node)
    {
        if ($this->functionLevel++ === 0) {
            if ($node->getObject()) {
                return false;
            } else {
                return $this->isFunctionSafe($node->getName());
            }
        }

        return true;
    }

    /**
     * @param OperatorNode $node
     *
     * @return bool
     */
    private function isFilterSafe(OperatorNode $node)
    {
        if ($this->functionLevel++ === 0) {
            return $this->isFunctionSafe($node->getChild(OperatorNode::OPERAND_RIGHT)->getName());
        }

        return true;
    }

    /**
     * @param $function
     *
     * @return mixed
     */
    private function isFunctionSafe($function)
    {
        $function = $this->environment->getFunction($function);
        $safeFor  = $function->getOption('is_safe');

        if (is_array($safeFor)) {
            if (in_array($this->autofilter, $safeFor, true)) {
                return true;
            }
        }

        return $safeFor === true || $safeFor === $this->autofilter;
    }
}
