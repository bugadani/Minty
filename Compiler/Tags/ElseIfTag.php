<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Tags;

use Modules\Templating\Compiler\Parser;
use Modules\Templating\Compiler\Tag;
use Modules\Templating\Compiler\TemplateCompiler;
use Modules\Templating\Compiler\Token;
use Modules\Templating\Compiler\TokenStream;

class ElseIfTag extends Tag
{

    public function getTag()
    {
        return 'elseif';
    }

    public function requiresState()
    {
        return array(
            array(Parser::STATE_BLOCK, 'if')
        );
    }

    public function setExpectations(TokenStream $stream)
    {
        $stream->expect(Token::EXPRESSION_START);
    }

    public function compile(TemplateCompiler $compiler)
    {
        $stream = $compiler->getTokenStream();

        $stream->nextToken();
        $expression_retval = $compiler->compileExpression($stream->nextToken());
        $compiler->outdent();
        $compiler->output('} elseif(%s) {', $expression_retval);
        $compiler->indent();
    }
}
