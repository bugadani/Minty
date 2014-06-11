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
use Modules\Templating\Compiler\Nodes\PrintNode;
use Modules\Templating\Compiler\Nodes\TagNode;
use Modules\Templating\Compiler\Operators\FilterOperator;
use Modules\Templating\Compiler\Parser;
use Modules\Templating\Compiler\Stream;
use Modules\Templating\Compiler\Tag;
use Modules\Templating\Compiler\Token;
use Modules\Templating\Environment;

class PrintTag extends Tag
{

    public function getTag()
    {
        return 'print';
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $expression = $parser->parseExpression($stream);
        if ($stream->current()->test(Token::PUNCTUATION, ':')) {
            $node = new TagNode($this);
            $node->addChild($parser->parseExpression($stream), 'value');
        } else {
            $node = new PrintNode();
            if (!$this->isSafe($parser->getEnvironment(), $expression)) {
                $function = new FunctionNode('filter', array($expression));
                $expression->setParent($function);
                $expression = $function;
            }
        }
        $node->addChild($expression, 'expression');

        return $node;
    }

    public function isFunctionSafe(Environment $env, $function)
    {
        if (!$env->hasFunction($function)) {
            return true;
        }

        return $env->getFunction($function)->getOption('is_safe');
    }

    public function isSafe(Environment $env, Node $node)
    {
        if ($env->getOption('autoescape') === false) {
            return true;
        }
        if ($node instanceof DataNode) {
            return true;
        }
        if ($node instanceof FunctionNode) {
            return $this->isFunctionSafe($env, $node->getName());
        }
        if (!$node instanceof OperatorNode) {
            return false;
        }
        if ($node->getOperator() instanceof FilterOperator) {
            /** @var $filter IdentifierNode|FunctionNode */
            $filter = $node->getChild(OperatorNode::OPERAND_RIGHT);

            return $this->isFunctionSafe($env, $filter->getName());
        } else {
            $safe = true;
            if ($node->hasChild(OperatorNode::OPERAND_LEFT)) {
                $safe &= $this->isSafe($env, $node->getChild(OperatorNode::OPERAND_LEFT));
            }
            if ($node->hasChild(OperatorNode::OPERAND_MIDDLE)) {
                $safe &= $this->isSafe($env, $node->getChild(OperatorNode::OPERAND_MIDDLE));
            }
            if ($node->hasChild(OperatorNode::OPERAND_RIGHT)) {
                $safe &= $this->isSafe($env, $node->getChild(OperatorNode::OPERAND_RIGHT));
            }

            return $safe;
        }

    }

    public function compile(Compiler $compiler, TagNode $node)
    {
        $compiler
            ->indented('')
            ->compileNode($node->getChild('expression'))
            ->add(' = ')
            ->compileNode($node->getChild('value'))
            ->add(';');
    }
}
