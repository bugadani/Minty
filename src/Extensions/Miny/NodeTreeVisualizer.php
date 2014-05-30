<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Extensions\Miny;

use Miny\Log\AbstractLog;
use Miny\Log\Log;
use Modules\Templating\Compiler\Node;
use Modules\Templating\Compiler\Nodes\DataNode;
use Modules\Templating\Compiler\Nodes\IdentifierNode;
use Modules\Templating\Compiler\Nodes\OperatorNode;
use Modules\Templating\Compiler\Nodes\RootNode;
use Modules\Templating\Compiler\Nodes\TagNode;
use Modules\Templating\Compiler\Nodes\VariableNode;
use Modules\Templating\Compiler\NodeVisitor;

/**
 * Class NodeTreeVisualizer
 *
 * The main goal of this class is to provide a tool to print out the Abstract Syntax Tree
 * for debug purposes.
 */
class NodeTreeVisualizer extends NodeVisitor
{
    private $level = 0;

    /**
     * @var AbstractLog
     */
    private $log;

    public function __construct(AbstractLog $log)
    {
        $this->log = $log;
    }

    public function getPriority()
    {
        //Set a low priority so that the optimization results are printed
        return 100;
    }

    public function enterNode(Node $node)
    {
        $str = $this->nodeToString($node);

        $this->log->write(Log::DEBUG, 'NodeTreeVisualizer', $str);

        ++$this->level;
    }

    public function leaveNode(Node $node)
    {
        --$this->level;

        return $node;
    }

    private function nodeToString(Node $node)
    {
        $string = str_repeat('|-', $this->level);
        $string .= get_class($node);

        if ($node instanceof RootNode) {
            $string .= ' (' . count($node->getChildren()) . ')';
        } elseif ($node instanceof TagNode) {
            $string .= " ({$node->getTag()->getTag()})";
        } elseif ($node instanceof OperatorNode) {
            $symbols = $node->getOperator()->operators();
            if (is_array($symbols)) {
                $symbols = implode(', ', $symbols);
            }
            $string .= " ({$symbols})";
        } elseif ($node instanceof IdentifierNode || $node instanceof VariableNode) {
            $string .= " ({$node->getName()})";
        } elseif ($node instanceof DataNode) {
            $data = $node->getData();
            if (is_bool($data)) {
                $string .= '(' . ($data ? 'true' : 'false') . ')';
            } elseif (is_scalar($data)) {
                $string .= "({$data})";
            } elseif (is_array($data)) {
                $count = count($data);
                $string .= "(array({$count}))";
            } else {
                $string .= '(object)';
            }
        }

        return $string;
    }
}
