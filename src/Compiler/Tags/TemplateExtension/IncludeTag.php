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
            ->indented('$template = $this->getLoader()->load(')
            ->compileData($node->getData('template'))
            ->add(');')
            ->indented('$template->set(')
            ->compileData($node->getData('arguments'))
            ->add(');')
            ->indented('$template->render();');
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $node = new TagNode($this, array(
            'template'  => $parser->parseExpression($stream)
        ));

        if ($stream->nextTokenIf(Token::EXPRESSION_START, 'using')) {
            $node->addData('arguments', $parser->parseExpression($stream));
        } else {
            $node->addData('arguments', array());
        }

        return $node;
    }
}
