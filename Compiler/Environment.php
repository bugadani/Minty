<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler;

use Modules\Templating\Compiler\Exceptions\CompileException;
use Modules\Templating\Compiler\Extensions\Core;
use Modules\Templating\Extension;
use Modules\Templating\TemplatingOptions;

class Environment
{
    private $tags;
    private $operators;
    private $binary_operator_signs;
    private $prefix_unary_operator_signs;
    private $postfix_unary_operator_signs;
    private $functions;
    private $options;
    private $extensions;

    public function __construct(TemplatingOptions $options)
    {
        $this->extensions                   = array();
        $this->functions                    = array();
        $this->operators                    = array();
        $this->binary_operator_signs        = array();
        $this->prefix_unary_operator_signs  = array();
        $this->postfix_unary_operator_signs = array();
        $this->options                      = $options;
        $this->addExtension(new Core());
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function addExtension(Extension $extension)
    {
        $this->extensions[$extension->getExtensionName()] = $extension;
        $extension->registerExtension($this);
    }

    public function addFunction(TemplateFunction $function)
    {
        $name                   = $function->getFunctionName();
        $this->functions[$name] = $function;
    }

    /**
     * @param string $name
     * @return Extension
     * @throws CompileException
     */
    public function getExtension($name)
    {
        if (!isset($this->extensions[$name])) {
            throw new CompileException('Extension not found: ' . $name);
        }
        return $this->extensions[$name];
    }

    /**
     * @param string $name
     * @return TemplateFunction
     * @throws CompileException
     */
    public function getFunction($name)
    {
        if (!isset($this->functions[$name])) {
            throw new CompileException('Function not found: ' . $name);
        }
        return $this->functions[$name];
    }

    public function getFunctions()
    {
        return $this->functions;
    }

    public function hasFunction($name)
    {
        return isset($this->functions[$name]);
    }

    public function addTag(Tag $tag)
    {
        $name              = $tag->getTag();
        $this->tags[$name] = $tag;
    }

    public function addBinaryOperator(Operator $operator)
    {
        $symbol = $operator->operators();
        if (is_array($symbol)) {
            foreach ($symbol as $op_symbol) {
                $this->binary_operator_signs[$op_symbol] = $operator;
            }
        } else {
            $this->binary_operator_signs[$symbol] = $operator;
        }
        $this->operators[] = $operator;
    }

    public function addPrefixUnaryOperator(Operator $operator)
    {
        $symbol = $operator->operators();
        if (is_array($symbol)) {
            foreach ($symbol as $op_symbol) {
                $this->prefix_unary_operator_signs[$op_symbol] = $operator;
            }
        } else {
            $this->prefix_unary_operator_signs[$symbol] = $operator;
        }
        $this->operators[] = $operator;
    }

    public function addPostfixUnaryOperator(Operator $operator)
    {
        $symbol = $operator->operators();
        if (is_array($symbol)) {
            foreach ($symbol as $op_symbol) {
                $this->postfix_unary_operator_signs[$op_symbol] = $operator;
            }
        } else {
            $this->postfix_unary_operator_signs[$symbol] = $operator;
        }
        $this->operators[] = $operator;
    }

    public function getBinaryOperatorSigns()
    {
        return $this->binary_operator_signs;
    }

    public function getPrefixUnaryOperatorSigns()
    {
        return $this->prefix_unary_operator_signs;
    }

    public function getPostfixUnaryOperatorSigns()
    {
        return $this->postfix_unary_operator_signs;
    }

    public function getTags()
    {
        return $this->tags;
    }

    public function getOperators()
    {
        return $this->operators;
    }
}
