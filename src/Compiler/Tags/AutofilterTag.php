<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Tags;

use Minty\Compiler\Compiler;
use Minty\Compiler\Nodes\TagNode;
use Minty\Compiler\Parser;
use Minty\Compiler\Stream;
use Minty\Compiler\Tag;
use Minty\Compiler\Token;

class AutofilterTag extends Tag
{

    public function hasEndingTag()
    {
        return true;
    }

    public function getTag()
    {
        return 'autofilter';
    }

    public function compile(Compiler $compiler, TagNode $node)
    {
        $compiler->compileNode($node->getChild('body'));
    }

    public function parse(Parser $parser, Stream $stream)
    {
        if ($stream->nextTokenIf(Token::IDENTIFIER)) {
            $token = $stream->expectCurrent(
                Token::IDENTIFIER,
                array('off', 'on', 'auto', 'disabled', 'enabled')
            );
        } else {
            $token = $stream->expect(Token::STRING);
        }
        switch ($token->getValue()) {
            case 'disabled':
            case 'off':
                $strategy = 0;
                break;

            case 'on':
            case 'enabled':
            case 'auto':
                $strategy = 1;
                break;

            default:
                $strategy = $token->getValue();
                break;
        }
        $node = new TagNode($this, array('strategy' => $strategy));
        $stream->expect(Token::TAG_END);

        $node->addChild(
            $parser->parseBlock($stream, 'endautofilter'),
            'body'
        );
        $stream->expect(Token::TAG_END);

        return $node;
    }
}
