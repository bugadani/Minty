<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Tags;

use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\Exceptions\SyntaxException;
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
        $compiler
            ->indented('switch(')
            ->compileNode($array['tested'])
            ->add(') {')
            ->indent();
        foreach ($array['branches'] as $branch) {
            if ($branch['condition'] === null) {
                $compiler->indented('default:');
            } else {
                $compiler
                    ->indented('case ')
                    ->compileNode($branch['condition'])
                    ->add(':');
            }
            $compiler->indent()
                ->compileNode($branch['body'])
                ->indented('break;')
                ->outdent();
        }
        $compiler
            ->outdent()
            ->indented('}');
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $branch = function (Stream $stream) {
            $token = $stream->next();
            if ($token->test(Token::EXPRESSION_START)) {
                return $stream->nextTokenIf(Token::IDENTIFIER, array('else', 'case'));
            }

            return $token->test(Token::TAG, 'endswitch');
        };

        $tested = $parser->parseExpression($stream);
        $stream->expect(Token::TEXT);
        $stream->expect(Token::EXPRESSION_START);
        $stream->next();

        $node = new TagNode($this, array(
            'tested' => $tested
        ));

        $branches = array();
        while (!$stream->current()->test(Token::TAG, 'endswitch')) {

            if ($stream->current()->test(Token::IDENTIFIER, 'case')) {
                $condition = $parser->parseExpression($stream);
            } elseif ($stream->current()->test(Token::IDENTIFIER, 'else')) {
                $stream->expect(Token::EXPRESSION_END);
                $condition = null;
            } else {
                throw new SyntaxException('Switch expects a case or else tag first.');
            }

            $branchNode = $parser->parse($stream, $branch);
            $branchNode->setParent($node);
            $branches[] = array(
                'condition' => $condition,
                'body'      => $branchNode
            );
        }

        $node->addData('branches', $branches);
        return $node;
    }
}
