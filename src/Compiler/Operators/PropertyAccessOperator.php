<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Operators;

use Minty\Compiler\Compiler;
use Minty\Compiler\Nodes\OperatorNode;
use Minty\Compiler\Operator;

class PropertyAccessOperator extends Operator
{

    public function operators()
    {
        return '.';
    }

    public function compile(Compiler $compiler, OperatorNode $node)
    {
        $args = $node->getChildren();
        ksort($args);

        $compiler
            ->add('$context->' . $this->getMethodName($node))
            ->compileArgumentList($args);
    }

    /**
     * @param OperatorNode $node
     *
     * @return string
     */
    private function getMethodName(OperatorNode $node)
    {
        if ($node->hasData('mode')) {
            $mode = $node->getData('mode');
        } else {
            $mode = 'get';
        }

        switch ($mode) {
            case 'has':
                return 'hasProperty';

            default:
            case 'get':
                return 'getProperty';

            case 'set':
                return 'setProperty';
        }
    }
}
