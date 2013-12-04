<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Tags\Blocks;

use Modules\Templating\Compiler\Parser;
use Modules\Templating\Compiler\Tags\Block;
use Modules\Templating\Compiler\TemplateCompiler;
use Modules\Templating\Compiler\Token;

class SwitchBlock extends Block
{

    public function getTag()
    {
        return 'switch';
    }

    public function compileEndingTag(TemplateCompiler $compiler)
    {
        $compiler->output('break;');
        $compiler->outdent();
        $compiler->outdent();
        $compiler->output('}');
    }

    public function parseExpression(Parser $parser, $expression)
    {
        if (empty($expression)) {
            $parser->throwException('unexpected', 'tag ending');
        }

        $stream = $parser->getTokenStream();
        $parser->parseExpression($expression);

        $stream->consumeNextWhitespace();
        $stream->expect(Token::TAG, 'case');
        $stream->expect(Token::TAG, 'else');
    }

    public function compile(TemplateCompiler $compiler)
    {
        $stream = $compiler->getTokenStream();
        $expr   = $compiler->compileExpression($stream->nextToken());
        $compiler->output('switch(%s) {', $expr);
        $compiler->indent();
        $compiler->pushState(TemplateCompiler::STATE_BLOCK_SWITCH);
    }
}
