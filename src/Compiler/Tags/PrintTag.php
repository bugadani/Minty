<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Tags;

use Minty\Compiler\Compiler;
use Minty\Compiler\Nodes\PrintNode;
use Minty\Compiler\Nodes\TagNode;
use Minty\Compiler\Parser;
use Minty\Compiler\Stream;
use Minty\Compiler\Tag;
use Minty\Compiler\Token;

class PrintTag extends Tag
{

    public function getTag()
    {
        return 'print';
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $expression = $parser->parseExpression($stream);
        if ($stream->current()->test(Token::PUNCTUATION, ':')) {
            $node = new TagNode($this);
            $node->addChild($parser->parseExpression($stream), 'value');
        } else {
            $node = new PrintNode();
        }

        $node->addChild($expression, 'expression');

        return $node;
    }

    public function compile(Compiler $compiler, TagNode $node)
    {
        $compiler
            ->indented('')
            ->compileNode($node->getChild('expression'))
            ->add(' = ')
            ->compileNode($node->getChild('value'))
            ->add(';');
    }
}
