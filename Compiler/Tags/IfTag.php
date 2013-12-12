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

    public function compile(Compiler $compiler, array $data)
    {
        $first = true;
        $else  = null;
        foreach ($data as $branch) {
            if ($first) {
                $compiler->indented('if(');
                $branch['condition']->compile($compiler);
                $compiler->add(') {');
                $this->compileBody($compiler, $branch['body']);
                $compiler->indented('}');
                $first = false;
            } else {
                if ($branch['condition'] === null) {
                    $else = $branch;
                } else {
                    $compiler->add(' elseif(');
                    $branch['condition']->compile($compiler);
                    $compiler->add(') {');
                    $this->compileBody($compiler, $branch['body']);
                    $compiler->indented('}');
                }
            }
        }
        if ($else !== null) {
            $compiler->add(' else {');
            $this->compileBody($compiler, $branch['body']);
            $compiler->indented('}');
        }
    }

    private function compileBody(Compiler $compiler, RootNode $body)
    {
        $compiler->indent();
        $body->compile($compiler);
        $compiler->outdent();
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $fork = function(Stream $stream) {
            $token = $stream->next();
            if ($token->test(Token::EXPRESSION_START)) {
                return $stream->nextTokenIf(Token::IDENTIFIER, array('else', 'elseif'));
            }
            return $token->test(Token::TAG, 'endif');
        };

        $branches  = array();
        $condition = $parser->parseExpression($stream);
        do {
            $branches[] = array(
                'condition' => $condition,
                'body'      => $parser->parse($stream, $fork)
            );
            if ($stream->current()->test(Token::IDENTIFIER, 'else')) {
                $stream->expect(Token::EXPRESSION_END);
                $condition = null;
            } elseif ($stream->current()->test(Token::IDENTIFIER, 'elseif')) {
                $condition = $parser->parseExpression($stream);
                $stream->next();
            }
        } while (!$stream->current()->test(Token::TAG, 'endif'));

        return new TagNode($this, $branches);
    }
}
