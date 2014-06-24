<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Nodes;

use Minty\Compiler\Compiler;
use Minty\Compiler\Node;
use Minty\Compiler\Tag;

class TagNode extends Node
{
    /**
     * @var Tag
     */
    private $tag;

    /**
     * @param Tag   $tag
     * @param array $data
     */
    public function __construct(Tag $tag, array $data = array())
    {
        $this->tag = $tag;
        $this->setData($data);
    }

    /**
     * @return Tag
     */
    public function getTag()
    {
        return $this->tag;
    }

    public function compile(Compiler $compiler)
    {
        if ($compiler->getEnvironment()->getOption('debug')) {
            $compiler->indented('//Line %d: %s tag', $this->getData('line'), $this->tag->getTag());
        }
        $this->tag->compile($compiler, $this);
    }
}
