<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Tags;

use Modules\Templating\Compiler\Tag;
use Modules\Templating\Compiler\TemplateCompiler;
use Modules\Templating\Compiler\Token;
use Modules\Templating\Compiler\TokenStream;

class ExtendsTag extends Tag
{

    public function getTag()
    {
        return 'extends';
    }

    public function setExpectations(TokenStream $stream)
    {
        $stream->expect(Token::EXPRESSION_START)->then(Token::STRING)->then(Token::EXPRESSION_END);
    }

    public function compile(TemplateCompiler $compiler)
    {
        $stream = $compiler->getTokenStream();

        $stream->nextToken();
        $template = $stream->nextToken()->getValue();
        $stream->nextToken();

        $compiler->setExtendedTemplate($template);
    }
}
