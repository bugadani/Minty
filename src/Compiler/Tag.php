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
    /**
     * Returns the tag name.
     *
     * @return string
     */
    abstract public function getTag();

    /**
     * Returns whether the tag has a corresponding ending tag, i.e. is a block tag.
     *
     * @return bool
     */
    public function hasEndingTag()
    {
        return false;
    }

    /**
     * @param Parser $parser
     * @param Stream $stream
     *
     * @throws \BadMethodCallException
     * @return void|Node
     */
    public function parse(Parser $parser, Stream $stream)
    {
        throw new \BadMethodCallException("The {$this->getTag()} tag should not be parsed");
    }

    /**
     * @param Compiler $compiler
     * @param TagNode  $data
     *
     * @throws \BadMethodCallException
     */
    public function compile(Compiler $compiler, TagNode $data)
    {
        throw new \BadMethodCallException("The {$this->getTag()} tag should not be compiled");
    }
}
