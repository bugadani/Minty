<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler;

use Minty\Compiler\Nodes\TagNode;

abstract class Tag
{

    abstract public function getTag();

    public function hasEndingTag()
    {
        return false;
    }

    /**
     * @param Parser $parser
     * @param Stream $stream
     * @return void|Node
     */
    public function parse(Parser $parser, Stream $stream)
    {
    }

    public function compile(Compiler $compiler, TagNode $data)
    {
    }
}
