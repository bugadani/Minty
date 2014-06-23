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

class ListTag extends Tag
{

    public function getTag()
    {
        return 'list';
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $node = new TagNode($this);

        $node->addChild($parser->parseExpression($stream), 'expression');
        $stream->expectCurrent(Token::IDENTIFIER, 'using');
        $node->addChild($parser->parseExpression($stream), 'template');
        $stream->expectCurrent(Token::EXPRESSION_END);

        return $node;
    }

    public function compile(Compiler $compiler, TagNode $node)
    {
        $compiler
            ->indented('foreach (')
            ->compileNode($node->getChild('expression'))
            ->add(' as $element) {')
            ->indent()
            ->indented('$environment->render(')
            ->compileNode($node->getChild('template'))
            ->add(', $element);')
            ->outdent()
            ->indented('}');
    }
}
