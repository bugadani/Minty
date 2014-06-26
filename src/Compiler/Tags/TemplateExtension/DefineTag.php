<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Tags\TemplateExtension;

use Minty\Compiler\Parser;
use Minty\Compiler\Stream;
use Minty\Compiler\Tag;
use Minty\Compiler\Token;

class DefineTag extends Tag
{
    public function hasEndingTag()
    {
        return true;
    }

    public function getTag()
    {
        return 'define';
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $templateName = $stream->expect(Token::IDENTIFIER)->getValue();
        $stream->expect(Token::EXPRESSION_END);

        $parser->enterBlock($templateName);
        $parser->getCurrentClassNode()->addChild(
            $parser->parseBlock($stream, 'enddefine'),
            $templateName
        );
        $parser->leaveBlock();
    }
}
