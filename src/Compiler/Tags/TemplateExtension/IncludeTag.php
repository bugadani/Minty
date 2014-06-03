<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Tags\TemplateExtension;

use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\Nodes\TagNode;
use Modules\Templating\Compiler\Parser;
use Modules\Templating\Compiler\Stream;
use Modules\Templating\Compiler\Tag;
use Modules\Templating\Compiler\Token;
use Modules\Templating\Compiler\Tokenizer;

class IncludeTag extends Tag
{

    public function getTag()
    {
        return 'include';
    }

    public function tokenize(Tokenizer $tokenizer, $expression)
    {
        $tokenizer->pushToken(Token::EXPRESSION_START, $this->getTag());

        if (!strpos($expression, 'using')) {
            $tokenizer->tokenizeExpression($expression);
        } else {
            list($template, $source) = explode('using', $expression);
            $tokenizer->tokenizeExpression($template);
            $tokenizer->pushToken(Token::EXPRESSION_END);
            $tokenizer->pushToken(Token::EXPRESSION_START, 'using');
            $tokenizer->tokenizeExpression($source);
        }

        $tokenizer->pushToken(Token::EXPRESSION_END);
    }

    public function compile(Compiler $compiler, TagNode $node)
    {
        $compiler
            ->indented('$this->getLoader()->render(')
            ->compileNode($node->getChild('template'));

        if ($node->hasChild('arguments')) {
            $compiler
                ->add(', ')
                ->compileNode($node->getChild('arguments'));
        }

        $compiler
            ->add(');');
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $node = new TagNode($this);

        $node->addChild($parser->parseExpression($stream), 'template');
        if ($stream->nextTokenIf(Token::EXPRESSION_START, 'using')) {
            $node->addChild($parser->parseExpression($stream), 'arguments');
        }

        return $node;
    }
}
