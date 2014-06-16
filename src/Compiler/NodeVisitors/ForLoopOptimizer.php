<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\NodeVisitors;

use Modules\Templating\Compiler\Node;
use Modules\Templating\Compiler\Nodes\TagNode;
use Modules\Templating\Compiler\NodeVisitor;
use Modules\Templating\Compiler\Tags\ForTag;

class ForLoopOptimizer extends NodeVisitor
{
    private $stack = array();
    private $counterStack = array();
    private $counter = 0;

    public function getPriority()
    {
        return 1;
    }

    public function enterNode(Node $node)
    {
        if (!$node instanceof TagNode) {
            return;
        }
        if (!$node->getTag() instanceof ForTag) {
            return;
        }
        if (!empty($this->stack)) {
            $node->addData('create_stack', false);
        }
        ++$this->counter;
        $this->counterStack[] = $this->counter;
        $this->stack[]        = $node;
    }

    public function leaveNode(Node $node)
    {
        if (!$node instanceof TagNode) {
            return true;
        }
        if (!$node->getTag() instanceof ForTag) {
            return true;
        }
        array_pop($this->stack);
        //if there are no for tags nested inside this for tag, or the for tag does not have an else branch
        //there's no need to save $temp
        if (array_pop($this->counterStack) === $this->counter || !$node->hasChild('else')) {
            $node->addData('save_temp_var', false);
        }

        return true;
    }
}
