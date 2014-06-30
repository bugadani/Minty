<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Minty;

use Minty\Compiler\NodeVisitor;
use Minty\Compiler\Operator;
use Minty\Compiler\Tag;
use Minty\Compiler\TemplateFunction;

abstract class Extension
{
    abstract public function getExtensionName();

    /**
     * @return TemplateFunction[]
     */
    public function getFunctions()
    {
        return array();
    }

    /**
     * @return Operator[]
     */
    public function getBinaryOperators()
    {
        return array();
    }

    /**
     * @return Operator[]
     */
    public function getPrefixUnaryOperators()
    {
        return array();
    }

    /**
     * @return Operator[]
     */
    public function getPostfixUnaryOperators()
    {
        return array();
    }

    /**
     * @return Tag[]
     */
    public function getTags()
    {
        return array();
    }

    /**
     * @return NodeVisitor
     */
    public function getNodeVisitors()
    {
        return array();
    }
}
