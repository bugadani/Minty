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

class SwitchTag extends Tag
{

    public function hasEndingTag()
    {
        return true;
    }

    public function getTag()
    {
        return 'switch';
    }

    public function compile(Compiler $compiler, array $array)
    {
        $compiler->indented('switch(');
        $array['tested']->compile($compiler);
        $compiler->add(') {');
        $compiler->indent();
        foreach ($array['branches'] as $branch) {
            if ($branch['condition'] === null) {
                $compiler->indented('default:');
            } else {
                $compiler->indented('case ');
                $branch['condition']->compile($compiler);
                $compiler->add(':');
            }
            $compiler->indent();
            $branch['body']->compile($compiler);
            $compiler->indented('break;');
            $compiler->outdent();
        }
        $compiler->outdent();
        $compiler->indented('}');
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $tested        = $parser->parseExpression($stream);
        $branches      = array();
        $body          = new RootNode();
        $first         = true;
        $branch_number = -1;
        while (!$stream->next()->test(Token::TAG, 'endswitch')) {
            if ($stream->nextTokenIf(Token::IDENTIFIER, 'case')) {
                $stream->expect(Token::EXPRESSION_END);
                $stream->expect(Token::EXPRESSION_START);
                if ($first) {
                    $first = false;
                } else {
                    $branches[$branch_number]['body'] = $body;
                }
                $branch_number++;
                $branches[] = array(
                    'condition' => $parser->parseExpression($stream)
                );
                $body       = new RootNode();
            } elseif ($stream->nextTokenIf(Token::IDENTIFIER, 'else')) {
                $stream->expect(Token::EXPRESSION_END);
                if ($first) {
                    $first = false;
                } else {
                    $branches[$branch_number]['body'] = $body;
                }
                $branch_number++;
                $branches[] = array(
                    'condition' => null
                );
                $body       = new RootNode();
            } else {
                if ($first) {
                    //ignore
                }
                $body->addChild($parser->parseToken($stream));
            }
        }
        $branches[$branch_number]['body'] = $body;

        $data = array(
            'tested'   => $tested,
            'branches' => $branches
        );
        return new TagNode($this, $data);
    }
}
