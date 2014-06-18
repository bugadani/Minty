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

        if ($stream->next()->test(Token::TEXT)) {
            $token = $stream->expect(Token::TAG, array('case', 'else'));
        } else {
            $token = $stream->expectCurrent(Token::TAG, array('case', 'else'));
        }

        while (!$token->test(Token::TAG, 'endswitch')) {
            $branch = $node->addChild(new RootNode());

            if ($token->test(Token::TAG, 'case')) {
                $stream->expect(Token::EXPRESSION_START);
                $branch->addChild($parser->parseExpression($stream), 'condition');
            } elseif (!$token->test(Token::TAG, 'else')) {
                throw new SyntaxException('Switch expects a case or else tag.');
            }

            $body = $parser->parseBlock($stream, array('else', 'case', 'endswitch'));
            $branch->addChild($body, 'body');
            $token = $stream->current();
        }

        return $node;
    }
}
