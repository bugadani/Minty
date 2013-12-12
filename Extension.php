<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENCE file.
 */

namespace Modules\Templating;

use Modules\Templating\Compiler\Environment;
use Modules\Templating\Compiler\Operator;
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

    public function registerExtension(Environment $environment)
    {
        foreach ($this->getFunctions() as $function) {
            $function->setExtensionName($this->extension_name);
            $environment->addFunction($function);
        }
        $binary        = $environment->getBinaryOperators();
        $unary_prefix  = $environment->getUnaryPrefixOperators();
        $unary_postfix = $environment->getUnaryPostfixOperators();
        foreach ($this->getBinaryOperators() as $operator) {
            $binary->addOperator($operator);
        }
        foreach ($this->getPrefixUnaryOperators() as $operator) {
            $unary_prefix->addOperator($operator);
        }
        foreach ($this->getPostfixUnaryOperators() as $operator) {
            $unary_postfix->addOperator($operator);
        }
        foreach ($this->getTags() as $tag) {
            $environment->addTag($tag);
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
}
