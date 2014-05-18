<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Modules\Templating;

use Modules\Templating\Compiler\NodeOptimizer;
use Modules\Templating\Compiler\Operator;
use Modules\Templating\Compiler\OperatorCollection;
use Modules\Templating\Compiler\Tag;
use Modules\Templating\Compiler\TemplateFunction;

abstract class Extension
{
    private $extension_name;

    public function __construct()
    {
        $this->extension_name = $this->getExtensionName();
    }

    abstract public function getExtensionName();

    public function registerFunctions(Environment $environment)
    {
        foreach ($this->getFunctions() as $function) {
            $function->setExtensionName($this->extension_name);
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

    public function registerUnaryPrefixOperators(OperatorCollection $unary_prefix)
    {
        foreach ($this->getPrefixUnaryOperators() as $operator) {
            $unary_prefix->addOperator($operator);
        }
    }

    public function registerUnaryPostfixOperators(OperatorCollection $unary_postfix)
    {
        foreach ($this->getPostfixUnaryOperators() as $operator) {
            $unary_postfix->addOperator($operator);
        }
    }

    public function registerNodeOptimizers(Environment $env)
    {
        foreach($this->getNodeOptimizers() as $optimizer) {
            $env->addNodeOptimizer($optimizer);
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
     * @return NodeOptimizer
     */
    private function getNodeOptimizers()
    {
        return array();
    }
}
