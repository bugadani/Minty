<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Tags\Blocks;

use Modules\Templating\Compiler\Tags\Block;
use Modules\Templating\Compiler\TemplateCompiler;
use Modules\Templating\Compiler\Token;
use Modules\Templating\Compiler\TokenStream;

class ForBlock extends Block
{

    public function getTag()
    {
        return 'for';
    }

    public function setExpectations(TokenStream $stream)
    {
        $stream->expect(Token::EXPRESSION_START, '(')->then(Token::IDENTIFIER)->then(Token::KEYWORD, 'in');
        $stream->expect(Token::EXPRESSION_START, '(')->then(Token::IDENTIFIER)->then(Token::OPERATOR, '=>');
    }

    public function compile(TemplateCompiler $compiler)
    {
        $stream  = $compiler->getTokenStream();
        $ident   = $stream->nextToken();
        $keyword = $stream->nextToken();

        if ($keyword->test(Token::OPERATOR, '=>')) {
            $key   = $ident;
            $ident = $stream->nextToken();
            $stream->nextToken();
            $expr  = $compiler->compileExpression($stream->nextToken());
            $compiler->output('foreach(%s as $this->%s => $this->%s) {', $expr, $key->getValue(), $ident->getValue());
        } else {
            $expr = $compiler->compileExpression($stream->nextToken());
            $compiler->output('foreach(%s as $this->%s) {', $expr, $ident->getValue());
        }
        $compiler->indent();
        $compiler->pushState(TemplateCompiler::STATE_BLOCK_FOR, $expr);
    }
}
