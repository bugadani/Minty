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

class ListTag extends Tag
{

    public function getTag()
    {
        return 'list';
    }

    public function requiresState()
    {
        return array(
            array(Parser::STATE_TEXT),
            array(Parser::STATE_BLOCK)
        );
    }

    public function setExpectations(TokenStream $stream)
    {
        $stream->expect(Token::EXPRESSION_START, '(')->then(Token::IDENTIFIER)
                ->then(Token::KEYWORD, 'using')->then(Token::STRING)->then(Token::EXPRESSION_END, ')');
        $stream->expect(Token::EXPRESSION_START, '(')->then(Token::ARGUMENT_LIST_START, 'array');
    }

    public function compile(TemplateCompiler $compiler)
    {
        $stream = $compiler->getTokenStream();
        $stream->nextToken();
        $list   = $stream->nextToken();
        if ($list->test(Token::ARGUMENT_LIST_START, 'array')) {
            $arguments = $compiler->processArgumentList($list);
        } else {
            $list      = $list->getValue();
            $arguments = '$this->' . $list;
        }

        if ($stream->nextTokenIf(Token::KEYWORD, 'using')) {
            $template = $stream->nextToken();
            $arguments .= ', ' . $compiler->addStringDelimiters($template);
        }
        $compiler->output('echo $this->listArrayElements(' . $arguments . ');');
    }
}
