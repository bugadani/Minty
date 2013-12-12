<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Nodes;

use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\Node;
use Modules\Templating\Compiler\Tag;

class TagNode extends Node
{
    /**
     * @var Tag
     */
    private $tag;
    private $data;

    /**
     * @param Tag $tag
     */
    public function __construct(Tag $tag, array $data = array())
    {
        $this->tag  = $tag;
        $this->data = $data;
    }

    public function compile(Compiler $compiler)
    {
        $this->tag->compile($compiler, $this->data);
    }
}
