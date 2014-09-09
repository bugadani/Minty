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

class SetTag extends Tag
{

    public function getTag()
    {
        return 'set';
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $node      = new TagNode($this);
        $variables = 0;

        do {
            $node->addChild($parser->parseExpression($stream), 'expression_' . $variables);
            $stream->expectCurrent(Token::PUNCTUATION, ':');
            $node->addChild($parser->parseExpression($stream), 'value_' . $variables);

            $variables++;
        } while ($stream->current()->test(Token::PUNCTUATION, ','));

        $node->addData('variables', $variables);

        return $node;
    }

    public function compile(Compiler $compiler, TagNode $node)
    {
        $varNum = $node->getData('variables');
        for ($i = 0; $i < $varNum; ++$i) {
            $compiler
                ->indented('')
                ->compileNode($node->getChild('expression_' . $i))
                ->add(' = ')
                ->compileNode($node->getChild('value_' . $i))
                ->add(';');
        }
    }
}
