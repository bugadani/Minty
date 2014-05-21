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
use Modules\Templating\Compiler\Node;
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
            ->openBracket();

        foreach ($node->getChildren() as $branch) {
            $this->compileCaseLabel($compiler, $branch);
            $compiler
                ->indent()
                ->compileNode($branch->getChild('body'))
                ->indented('break;')
                ->outdent();
        }
        $compiler->closeBracket();
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $branchTest = function (Token $token) {
            if ($token->test(Token::EXPRESSION_START, array('else', 'case'))) {
                return true;
            }

            return $token->test(Token::TAG, 'endswitch');
        };

        $node = new TagNode($this, array(
            'tested' => $parser->parseExpression($stream)
        ));

        if ($stream->next()->test(Token::TEXT)) {
            $token = $stream->expect(Token::EXPRESSION_START);
        } else {
            $token = $stream->expectCurrent(Token::EXPRESSION_START);
        }

        while (!$token->test(Token::TAG, 'endswitch')) {
            $branch = $node->addChild(new RootNode());

            if ($token->test(Token::EXPRESSION_START, 'case')) {
                $branch->addChild($parser->parseExpression($stream), 'condition');
            } elseif ($token->test(Token::EXPRESSION_START, 'else')) {
                $stream->expect(Token::EXPRESSION_END);
            } else {
                throw new SyntaxException('Switch expects a case or else tag first.');
            }

            $branch->addChild($parser->parse($stream, $branchTest), 'body');
            $token = $stream->current();
        }

        return $node;
    }
}
