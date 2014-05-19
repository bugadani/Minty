<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler;

class NodeTreeTraverser
{
    /**
     * @var NodeVisitor[]
     */
    private $visitors = array();

    /**
     * @param NodeVisitor[] $visitors
     */
    public function __construct(array $visitors = array())
    {
        foreach ($visitors as $visitor) {
            $this->addVisitor($visitor);
        }
    }

    public function addVisitor(NodeVisitor $visitor)
    {
        $priority = $visitor->getPriority();
        if (!isset($this->visitors[$priority])) {
            $this->visitors[$priority] = array();
        }
        $this->visitors[$priority][] = $visitor;
    }

    public function traverse(Node $node)
    {
        ksort($this->visitors);
        foreach ($this->visitors as $visitors) {
            foreach ($visitors as $visitor) {
                $this->visitNode($node, $visitor);
            }
        }
    }

    /**
     * @param Node        $node
     * @param NodeVisitor $visitor
     */
    private function visitNode(Node $node, NodeVisitor $visitor)
    {
        $visitor->enterNode($node);

        foreach ($node->getChildren() as $key => $child) {
            if (!$this->visitNode($child, $visitor)) {
                $node->removeChild($key);
            }
        }

        return $visitor->leaveNode($node);
    }
}
