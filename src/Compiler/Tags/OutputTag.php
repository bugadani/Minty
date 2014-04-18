<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Tags;

use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\Node;
use Modules\Templating\Compiler\Nodes\DataNode;
use Modules\Templating\Compiler\Nodes\FunctionNode;
use Modules\Templating\Compiler\Nodes\IdentifierNode;
use Modules\Templating\Compiler\Nodes\OperatorNode;
use Modules\Templating\Compiler\Nodes\TagNode;
use Modules\Templating\Compiler\Operators\FilterOperator;
use Modules\Templating\Compiler\Parser;
use Modules\Templating\Compiler\Stream;
use Modules\Templating\Compiler\Tag;
use Modules\Templating\Environment;

class OutputTag extends Tag
{

    public function getTag()
    {
        return 'output';
    }

    public function parse(Parser $parser, Stream $stream)
    {
        return new TagNode($this, array(
            'expression' => $parser->parseExpression($stream)
        ));
    }

    public function isFunctionSafe(Environment $env, $function)
    {
        if (!$env->hasFunction($function)) {
            return true;
        }

        return $env->getFunction($function)->isSafe();
    }

    public function isSafe(Environment $env, Node $node)
    {
        if ($node instanceof DataNode) {
            return true;
        }
        if ($node instanceof FunctionNode) {
            return $this->isFunctionSafe($env, $node->getFunctionName());
        }
        if (!$node instanceof OperatorNode) {
            return false;
        }
        if ($node->getOperator() instanceof FilterOperator) {
            /** @var $filter IdentifierNode|FunctionNode */
            $filter = $node->getOperand(OperatorNode::OPERAND_RIGHT);
            if ($filter instanceof IdentifierNode) {
                $function = $filter->getName();
            } else {
                $function = $filter->getFunctionName();
            }

            return $this->isFunctionSafe($env, $function);
        } else {
            $safe = true;
            if ($node->hasOperand(OperatorNode::OPERAND_LEFT)) {
                $safe &= $this->isSafe($env, $node->getOperand(OperatorNode::OPERAND_LEFT));
            }
            if ($node->hasOperand(OperatorNode::OPERAND_MIDDLE)) {
                $safe &= $this->isSafe($env, $node->getOperand(OperatorNode::OPERAND_MIDDLE));
            }
            if ($node->hasOperand(OperatorNode::OPERAND_RIGHT)) {
                $safe &= $this->isSafe($env, $node->getOperand(OperatorNode::OPERAND_RIGHT));
            }

            return $safe;
        }

    }

    public function ensureSafe(Environment $env, Node $node)
    {
        if ($env->getOption('autoescape') === false) {
            return true;
        }
        if ($this->isSafe($env, $node)) {
            return $node;
        }

        return new FunctionNode('filter', array($node));
    }

    public function compile(Compiler $compiler, array $data)
    {
        $expression = $this->ensureSafe($compiler->getEnvironment(), $data['expression']);
        $compiler
            ->indented('echo ')
            ->compileNode($expression)
            ->add(';');
    }
}
