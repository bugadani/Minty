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
use Modules\Templating\Compiler\OperatorCollection;
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

    public function registerFunctions(Environment $environment)
    {
        foreach ($this->getFunctions() as $function) {
            $environment->addFunction($function);
        }
    }

    public function registerTags(Environment $environment)
    {
        foreach ($this->getTags() as $tag) {
            $environment->addTag($tag);
        }
    }

    public function registerBinaryOperators(OperatorCollection $binary)
    {
        foreach ($this->getBinaryOperators() as $operator) {
            $binary->addOperator($operator);
        }
    }

    public function registerUnaryPrefixOperators(OperatorCollection $unaryPrefix)
    {
        foreach ($this->getPrefixUnaryOperators() as $operator) {
            $unaryPrefix->addOperator($operator);
        }
    }

    public function registerUnaryPostfixOperators(OperatorCollection $unaryPostfix)
    {
        foreach ($this->getPostfixUnaryOperators() as $operator) {
            $unaryPostfix->addOperator($operator);
        }
    }

    public function registerNodeVisitors(Environment $env)
    {
        foreach ($this->getNodeVisitors() as $optimizer) {
            $env->addNodeVisitor($optimizer);
        }
    }

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
