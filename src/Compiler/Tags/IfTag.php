<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Tags;

use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\Nodes\RootNode;
use Modules\Templating\Compiler\Nodes\TagNode;
use Modules\Templating\Compiler\Parser;
use Modules\Templating\Compiler\Stream;
use Modules\Templating\Compiler\Tag;
use Modules\Templating\Compiler\Token;

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

            $body = $parser->parseBlock($stream, array('else', 'elseif', 'endif'));
            $branchNode->addChild($body, 'body');

            $token = $stream->expectCurrent(Token::TAG);
            $tagName = $token->getValue();
            if ($tagName === 'else') {
                $condition = null;
            } elseif ($tagName === 'elseif') {
                $stream->expect(Token::EXPRESSION_START);
                $condition = $parser->parseExpression($stream);
            }
        } while ($tagName !== 'endif');

        return $node;
    }
}
