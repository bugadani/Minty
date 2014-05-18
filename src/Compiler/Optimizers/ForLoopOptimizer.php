<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Optimizers;

use Modules\Templating\Compiler\Node;
use Modules\Templating\Compiler\NodeOptimizer;
use Modules\Templating\Compiler\Nodes\TagNode;
use Modules\Templating\Compiler\Tags\ForTag;

class ForLoopOptimizer extends NodeOptimizer
{
    private static $forClass = 'Modules\\Templating\\Compiler\\Tags\\ForTag';

    public function getPriority()
    {
        return 1;
    }

    public function enterNode(Node $node)
    {
        if(!$node instanceof TagNode) {
            return;
        }
        if (!$node->getTag() instanceof ForTag) {
            return;
        }
        //If there are no child for tags, we don't need to save the temporary variable
        if(!$this->nodeHasChild($node, self::$forClass)) {
            $node->addData('save_temp_var', false);
        }
        //If there are parent for tags, the stack is already created
        if($this->nodeHasParent($node, self::$forClass)) {
            $node->addData('create_stack', false);
        }
    }

    public function leaveNode(Node $node)
    {

    }
}
