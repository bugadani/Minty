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

class IfBlock extends Block
{

    public function getTag()
    {
        return 'if';
    }

    public function setExpectations(TokenStream $stream)
    {
        $stream->expect(Token::EXPRESSION_START, '(');
    }

    public function compile(TemplateCompiler $compiler)
    {
        $stream = $compiler->getTokenStream();
        $expr   = $compiler->compileExpression($stream->nextToken());
        $compiler->output('if(%s) {', $expr);
        $compiler->indent();
        $compiler->pushState(TemplateCompiler::STATE_BLOCK_IF);
    }
}
