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
            $template = $expression;
        } else {
            list($template, $source) = explode('using', $expression);
        }
        $tokenizer->tokenizeExpression($template);
        $tokenizer->pushToken(Token::EXPRESSION_END);

        if (isset($source)) {
            $tokenizer->pushToken(Token::IDENTIFIER, 'using');

            $tokenizer->pushToken(Token::EXPRESSION_START);
            $tokenizer->tokenizeExpression($source);
            $tokenizer->pushToken(Token::EXPRESSION_END);
        }
    }

    public function compile(Compiler $compiler, array $data)
    {
        $compiler
            ->indented('$template = $this->getLoader()->load(')
            ->compileData($data['template'])
            ->add(');')
            ->indented('$template->set(')
            ->compileData($data['arguments'])
            ->add(');')
            ->indented('$template->render();');
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $name = $parser->parseExpression($stream);

        if ($stream->nextTokenIf(Token::IDENTIFIER, 'using')) {
            $stream->expect(Token::EXPRESSION_START);
            $arguments = $parser->parseExpression($stream);
        } else {
            $arguments = array();
        }

        $data = array(
            'template'  => $name,
            'arguments' => $arguments
        );

        return new TagNode($this, $data);
    }
}
