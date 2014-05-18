<?php

/**
 * This file is part of the Miny framework.
 * (c) DÃÂ¡niel Buga <daniel@bugadani.hu>
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

class ExtractTag extends Tag
{

    public function getTag()
    {
        return 'extract';
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $node = new TagNode($this);

        $node->addChild($parser->parseExpression($stream), 'keys');
        $stream->expectCurrent(Token::IDENTIFIER, 'from');
        $node->addChild($parser->parseExpression($stream), 'source');

        return $node;
    }

    public function compile(Compiler $compiler, TagNode $node)
    {
        $compiler
            ->indented('$this->extract(')
            ->compileNode($node->getChild('source'))
            ->add(', ')
            ->compileNode($node->getChild('keys'))
            ->add(');');
    }
}
