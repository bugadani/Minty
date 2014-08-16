<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Tags;

use Minty\Compiler\Compiler;
use Minty\Compiler\Nodes\RootNode;
use Minty\Compiler\Nodes\TagNode;
use Minty\Compiler\Parser;
use Minty\Compiler\Stream;
use Minty\Compiler\Tag;
use Minty\Compiler\Token;

class IfTag extends Tag
{

    public function hasEndingTag()
    {
        return true;
    }

    public function getTag()
    {
        return 'if';
    }

    public function compile(Compiler $compiler, TagNode $node)
    {
        $first = true;
        $else  = null;
        foreach ($node->getChildren() as $branch) {
            if ($first) {
                $compiler->indented('if(');
                $first = false;
            } elseif (!$branch->hasChild('condition')) {
                $else = $branch->getChild('body');
                continue;
            } else {
                $compiler->add(' elseif(');
            }

            $compiler
                ->compileNode($branch->getChild('condition'))
                ->add(') {')
                ->indent()
                ->compileNode($branch->getChild('body'))
                ->outdent()
                ->indented('}');
        }
        if ($else !== null) {
            $compiler
                ->add(' else {')
                ->indent()
                ->compileNode($else)
                ->outdent()
                ->indented('}');
        }
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $node      = new TagNode($this);
        $condition = $parser->parseExpression($stream);

        do {
            $branchNode = $node->addChild(new RootNode());

            if ($condition !== null) {
                $branchNode->addChild($condition, 'condition');
            }

            $body = $parser->parseBlock($stream, ['else', 'elseif', 'endif']);
            $branchNode->addChild($body, 'body');

            $tagName = $stream->current()->getValue();
            if ($tagName === 'else') {
                $condition = null;
                $stream->expect(Token::TAG_END);
            } elseif ($tagName === 'elseif') {
                $condition = $parser->parseExpression($stream);
            }
        } while ($tagName !== 'endif');

        $stream->expect(Token::TAG_END);

        return $node;
    }
}
