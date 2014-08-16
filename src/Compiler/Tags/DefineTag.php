<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Tags;

use Minty\Compiler\Exceptions\ParseException;
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
        $templateNameToken = $stream->expect(Token::IDENTIFIER);
        $templateName      = $templateNameToken->getValue();
        $stream->expect(Token::TAG_END);

        $classNode = $parser->getCurrentClassNode();
        if ($classNode->hasChild($templateName)) {
            throw new ParseException(
                "Block {$templateName} is already defined",
                $templateNameToken->getLine()
            );
        }

        $parser->enterBlock($templateName);
        $parser->getCurrentClassNode()->addChild(
            $parser->parseBlock($stream, 'enddefine'),
            $templateName
        );
        $stream->expect(Token::TAG_END);
        $parser->leaveBlock();
    }
}
