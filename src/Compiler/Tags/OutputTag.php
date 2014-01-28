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
        $data               = array();
        $data['expression'] = $parser->parseExpression($stream);
        return new TagNode($this, $data);
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
        } elseif ($node instanceof FunctionNode) {
            return $this->isFunctionSafe($env, $node->getFunctionName()->getName());
        } elseif ($node instanceof OperatorNode) {
            if ($node->getOperator() instanceof FilterOperator) {
                $filter = $node->getOperand(OperatorNode::OPERAND_RIGHT);
                if ($filter instanceof IdentifierNode) {
                    $function = $filter->getName();
                } else {
                    $function = $filter->getFunctionName()->getName();
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
            }
        }
        return false;
    }

    public function ensureSafe(Environment $env, Node $node)
    {
        $options = $env->getOptions();
        if ($options['autoescape'] === false) {
            return true;
        }
        if ($this->isSafe($env, $node)) {
            return $node;
        }
        $return = new FunctionNode(new IdentifierNode('filter'));
        $return->addArgument($node);
        return $return;
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
