<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Modules\Templating;

use Modules\Templating\Compiler\NodeVisitor;
use Modules\Templating\Compiler\Operator;
use Modules\Templating\Compiler\Tag;
use Modules\Templating\Compiler\TemplateFunction;

abstract class Extension
{
    private $extensionName;

    public function __construct()
    {
        $this->extensionName = $this->getExtensionName();
    }

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
