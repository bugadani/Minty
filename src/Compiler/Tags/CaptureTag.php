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

class CaptureTag extends Tag
{

    public function hasEndingTag()
    {
        return true;
    }

    public function getTag()
    {
        return 'capture';
    }

    public function compile(Compiler $compiler, TagNode $node)
    {
        $compiler->indented('ob_start();');

        $compiler->compileNode($node->getChild('body'));

        $compiler->indented('')
            ->compileNode($node->getChild('into'))
            ->add(' = ob_get_clean();');
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $stream->expect(Token::IDENTIFIER, 'into');

        $node = new TagNode($this);
        $node->addChild($parser->parseExpression($stream), 'into');
        $node->addChild($parser->parseBlock($stream, 'endcapture'), 'body');

        return $node;
    }
}
