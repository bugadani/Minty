<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler;

use Modules\Templating\Compiler\Nodes\TagNode;

abstract class Tag
{

    abstract public function getTag();

    public function hasEndingTag()
    {
        return false;
    }

    abstract public function parse(Parser $parser, Stream $stream);

    abstract public function compile(Compiler $compiler, TagNode $data);
}
