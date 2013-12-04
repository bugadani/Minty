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

class CaseTag extends Tag
{

    public function getTag()
    {
        return 'case';
    }

    public function requiresState()
    {
        return array(
            array(Parser::STATE_BLOCK, 'switch')
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

        switch ($compiler->getState()) {

            case TemplateCompiler::STATE_BLOCK_SWITCH_HAS_CASE:
                $compiler->output('break;');
                $compiler->output('');
                $compiler->outdent();
            /* intentional */

            case TemplateCompiler::STATE_BLOCK_SWITCH:
                $compiler->output('case %s:', $expression_retval);
                $compiler->indent();
                break;
        }

        $compiler->pushState(TemplateCompiler::STATE_BLOCK_SWITCH_HAS_CASE);
    }
}
