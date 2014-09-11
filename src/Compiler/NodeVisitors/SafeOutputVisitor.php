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
use Minty\Compiler\Nodes\OperatorNode;
use Minty\Compiler\Nodes\TagNode;
use Minty\Compiler\Nodes\VariableNode;
use Minty\Compiler\NodeVisitor;
use Minty\Compiler\Operators\FilterOperator;
use Minty\Compiler\Tags\AutofilterTag;
use Minty\Compiler\Tags\PrintTag;
use Minty\Environment;
use Minty\EnvironmentAwareInterface;

class SafeOutputVisitor extends NodeVisitor implements EnvironmentAwareInterface
{
    /**
     * @var Environment
     */
    private $environment;
    private $inTag = false;
    private $functionLevel = 0;
    private $isSafe;
    private $autofilter;
    private $autofilterStack = [];
    private $defaultAutofilterStrategy;
    private $extension;

    public function getPriority()
    {
        return 1;
    }

    public function setEnvironment(Environment $environment)
    {
        $this->environment               = $environment;
        $this->autofilter                = $environment->getOption('autofilter');
        $this->defaultAutofilterStrategy = $environment->getOption('default_autofilter_strategy');
    }

    public function enterNode(Node $node)
    {
        if ($node instanceof ClassNode) {
            $this->setupTemplateDefaults($node);
        } elseif ($this->inTag) {
            $this->checkForUnsafeAccess($node);
        } elseif ($this->isPrintNode($node)) {
            $this->inTag  = true;
            $this->isSafe = true;
        } elseif ($this->isAutofilterTag($node)) {
            $this->enterAutofilterNode($node);
        }
    }

    /**
     * @param ClassNode $node
     */
    private function setupTemplateDefaults(ClassNode $node)
    {
        if ($this->autofilter === 1) {
            $this->extension         = $this->getExtension($node->getTemplateName());
            $this->autofilterStack[] = $this->autofilter;
            $this->autofilter        = $this->extension;
        }
    }

    /**
     * @param $template
     *
     * @return string
     */
    private function getExtension($template)
    {
        $dot = strrpos($template, '.');
        if ($dot === false) {
            return $this->defaultAutofilterStrategy;
        }

        return substr($template, $dot + 1);
    }

    /**
     * @param Node $node
     */
    private function checkForUnsafeAccess(Node $node)
    {
        if (!$this->autofilter) {
            return;
        }
        if ($this->isFilterOperator($node)) {
            /** @var $node OperatorNode */
            $this->isSafe &= $this->isFilterSafe($node);
        } elseif ($node instanceof FunctionNode) {
            $this->isSafe &= $this->isFunctionNodeSafe($node);
        } elseif ($this->functionLevel === 0) {
            if ($this->isUnsafeVariable($node)) {
                $this->isSafe = false;
            }
        }
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
     * @param OperatorNode $node
     *
     * @return bool
     */
    private function isFilterSafe(OperatorNode $node)
    {
        if ($this->functionLevel++ === 0) {
            return $this->isFunctionSafe(
                $node->getChild(OperatorNode::OPERAND_RIGHT)
            );
        }

        return true;
    }

    private function isFunctionNodeSafe(FunctionNode $node)
    {
        if ($this->functionLevel++ === 0) {
            if ($node->getObject()) {
                return false;
            }

            return $this->isFunctionSafe($node);
        }

        return true;
    }

    private function isFunctionSafe(Node $function)
    {
        if (!$this->environment->hasFunction($function->getData('name'))) {
            return false;
        }
        $safeFor = $this->environment
            ->getFunction($function->getData('name'))
            ->getOption('is_safe');

        if (is_array($safeFor)) {
            return in_array($this->autofilter, $safeFor, true);
        }

        return $safeFor === true || $safeFor === $this->autofilter;
    }

    private function isUnsafeVariable(Node $node)
    {
        return $node instanceof VariableNode && $node->getData('name') !== '_self';
    }

    private function isPrintNode(Node $node)
    {
        return $node instanceof TagNode && $node->getTag() instanceof PrintTag;
    }

    private function isAutofilterTag(Node $node)
    {
        return $node instanceof TagNode && $node->getTag() instanceof AutofilterTag;
    }

    /**
     * @param Node $node
     */
    private function enterAutofilterNode(Node $node)
    {
        $this->autofilterStack[] = $this->autofilter;
        $strategy                = $node->getData('strategy');
        $this->autofilter        = $strategy === 1 ? $this->extension : $strategy;
    }

    public function leaveNode(Node $node)
    {
        if ($this->inTag) {
            if ($this->isPrintNode($node)) {
                if ($this->autofilter && !$this->isSafe) {
                    $this->addFilterNode($node, $this->autofilter);
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
     * @param      $for
     */
    private function addFilterNode(Node $node, $for)
    {
        if (!$this->environment->hasFunction('filter_' . $for)) {
            $for = $this->defaultAutofilterStrategy;
        }
        $node->addChild(
            new FunctionNode('filter_' . $for, [
                $node->getChild('expression')
            ]),
            'expression'
        );
    }
}
