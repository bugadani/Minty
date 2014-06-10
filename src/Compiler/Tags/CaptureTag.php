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
        $node = new TagNode($this);

        $stream->expect(Token::IDENTIFIER, 'into');

        $node->addChild(
            $parser->parseExpression($stream),
            'into'
        );

        $node->addChild(
            $parser->parseBlock($stream, 'endcapture'),
            'body'
        );

        return $node;
    }
}
