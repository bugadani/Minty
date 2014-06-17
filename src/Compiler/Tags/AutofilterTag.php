<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Tags;

use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\Nodes\TagNode;
use Modules\Templating\Compiler\Parser;
use Modules\Templating\Compiler\Stream;
use Modules\Templating\Compiler\Tag;
use Modules\Templating\Compiler\Token;

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
            $token = $stream->expectCurrent(Token::IDENTIFIER, array('off', 'on', 'auto'));
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
        $stream->expect(Token::EXPRESSION_END);

        $node->addChild(
            $parser->parseBlock($stream, 'endautofilter'),
            'body'
        );

        return $node;
    }
}
