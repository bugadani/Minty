<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Tags;

use Minty\Compiler\Compiler;
use Minty\Compiler\Exceptions\SyntaxException;
use Minty\Compiler\Node;
use Minty\Compiler\Nodes\RootNode;
use Minty\Compiler\Nodes\TagNode;
use Minty\Compiler\Parser;
use Minty\Compiler\Stream;
use Minty\Compiler\Tag;
use Minty\Compiler\Token;

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

    /**
     * @param Compiler $compiler
     * @param Node     $branch
     */
    private function compileCaseLabel(Compiler $compiler, Node $branch)
    {
        if (!$branch->hasChild('condition')) {
            $compiler->indented('default:');
        } else {
            $compiler
                ->indented('case ')
                ->compileNode($branch->getChild('condition'))
                ->add(':');
        }
    }

    public function compile(Compiler $compiler, TagNode $node)
    {
        $compiler
            ->indented('switch(')
            ->compileNode($node->getData('tested'))
            ->add(')')
            ->add(' {')
            ->indent();

        foreach ($node->getChildren() as $branch) {
            $this->compileCaseLabel($compiler, $branch);
            $compiler
                ->indent()
                ->compileNode($branch->getChild('body'))
                ->indented('break;')
                ->outdent();
        }
        $compiler
            ->outdent()
            ->indented('}');
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $node = new TagNode($this, array(
            'tested' => $parser->parseExpression($stream)
        ));

        $stream->nextTokenIf(Token::TEXT);
        $token = $stream->expect(Token::TAG_START, array('case', 'else'));

        $hasDefault = false;
        while (!$token->test(Token::TAG_START, 'endswitch')) {
            $branch = $node->addChild(new RootNode());

            if ($token->test(Token::TAG_START, 'case')) {
                $branch->addChild($parser->parseExpression($stream), 'condition');
            } elseif ($token->test(Token::TAG_START, 'else')) {
                if($hasDefault) {
                    throw new SyntaxException('Switch blocks may only contain one else tag', $token->getLine());
                }
                $stream->expect(Token::TAG_END);
                $hasDefault = true;
            } else {
                throw new SyntaxException('Switch expects a case or else tag.');
            }

            $body = $parser->parseBlock($stream, array('else', 'case', 'endswitch'));
            $branch->addChild($body, 'body');
            $token = $stream->current();
        }
        $stream->expect(Token::TAG_END);

        return $node;
    }
}
