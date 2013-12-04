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

class TemplateBlock extends Block
{

    public function getTag()
    {
        return 'template';
    }

    public function setExpectations(TokenStream $stream)
    {
        $expectations = array(
            array(Token::EXPRESSION_START, '('),
            array(Token::LITERAL, null, 2),
            array(Token::STRING, null, 2),
            array(Token::EXPRESSION_END, null, 3)
        );
        $stream->expect($expectations[0], $expectations[1], $expectations[3]);
        $stream->expect($expectations[0], $expectations[2], $expectations[3]);
    }

    public function compile(TemplateCompiler $compiler)
    {
        $stream = $compiler->getTokenStream();
        $compiler->startTemplate($stream->nextToken()->getValue());
        $stream->nextToken();
        $compiler->pushState(TemplateCompiler::STATE_BLOCK_TEMPLATE);
    }

    public function compileEndingTag(TemplateCompiler $compiler)
    {
        $compiler->endTemplate();
    }
}
