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
        //print_r($data);exit;
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
        $test     = $parser->parseExpression($stream);
        $branches = array();
        $body     = new RootNode();
        while (!$stream->next()->test(Token::TAG, 'endif')) {
            $token = $stream->current();
            if ($token->test(Token::EXPRESSION_START)) {
                if ($stream->nextTokenIf(Token::IDENTIFIER, 'else')) {
                    $branches[] = array(
                        'condition' => $test,
                        'body'      => $body
                    );
                    $body       = new RootNode();
                    $test       = null;
                    $stream->expect(Token::EXPRESSION_END);
                } elseif ($stream->nextTokenIf(Token::IDENTIFIER, 'elseif')) {
                    $stream->expect(Token::EXPRESSION_END);
                    $stream->expect(Token::EXPRESSION_START);
                    $branches[] = array(
                        'condition' => $test,
                        'body'      => $body
                    );
                    $body       = new RootNode();
                    $test       = $parser->parseExpression($stream);
                } else {
                    $body->addChild($parser->parseToken($stream));
                }
            } else {
                $body->addChild($parser->parseToken($stream));
            }
        }
        $branches[] = array(
            'condition' => $test,
            'body'      => $body
        );
        return new TagNode($this, $branches);
    }
}
