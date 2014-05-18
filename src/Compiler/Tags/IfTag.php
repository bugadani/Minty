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
                ->add(') ')
                ->bracketed($branch->getChild('body'));
        }
        if ($else !== null) {
            $compiler
                ->add(' else ')
                ->bracketed($else);
        }
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $fork = function (Stream $stream) {
            $token = $stream->next();
            if ($token->test(Token::EXPRESSION_START)) {
                return $stream->nextTokenIf(Token::IDENTIFIER, array('else', 'elseif'));
            }

            return $token->test(Token::TAG, 'endif');
        };

        $node      = new TagNode($this);
        $condition = $parser->parseExpression($stream);

        do {
            $branchNode = $node->addChild(new RootNode());

            if ($condition !== null) {
                $branchNode->addChild($condition, 'condition');
            }
            $branchNode->addChild($parser->parse($stream, $fork), 'body');

            if ($stream->current()->test(Token::IDENTIFIER, 'else')) {
                $stream->expect(Token::EXPRESSION_END);
                $condition = null;
            } elseif ($stream->current()->test(Token::IDENTIFIER, 'elseif')) {
                $condition = $parser->parseExpression($stream);
                $stream->next();
            }
        } while (!$stream->current()->test(Token::TAG, 'endif'));

        return $node;
    }
}
