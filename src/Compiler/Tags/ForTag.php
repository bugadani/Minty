<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Tags;

use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\Nodes\TagNode;
use Modules\Templating\Compiler\Parser;
use Modules\Templating\Compiler\Stream;
use Modules\Templating\Compiler\Tag;
use Modules\Templating\Compiler\Token;
use Modules\Templating\Compiler\Tokenizer;

class ForTag extends Tag
{

    public function hasEndingTag()
    {
        return true;
    }

    public function getTag()
    {
        return 'for';
    }

    public function tokenize(Tokenizer $tokenizer, $expression)
    {
        $tokenizer->pushToken(Token::EXPRESSION_START, $this->getTag());

        list($vars, $source) = explode(' in ', $expression, 2);
        if (strpos($vars, ':') !== false) {
            list($key, $vars) = explode(':', $vars, 2);
            $tokenizer->pushToken(Token::IDENTIFIER, trim($key));
            $tokenizer->pushToken(Token::PUNCTUATION, ':');
        }
        $tokenizer->pushToken(Token::IDENTIFIER, trim($vars));

        $tokenizer->pushToken(Token::IDENTIFIER, 'in');
        $tokenizer->tokenizeExpression($source);
        $tokenizer->pushToken(Token::EXPRESSION_END);
    }

    public function compile(Compiler $compiler, TagNode $node)
    {
        $data = $node->getData();
        if ($data['save_temp_var']) {
            $compiler
                ->indented('if (isset($temp))')
                ->openBracket();

            if ($data['create_stack']) {
                $compiler->indented('$stack = array();');
            }

            $compiler
                ->indented('$stack[] = $temp;')
                ->closeBracket();
        }

        if ($node->hasChild('else')) {
            $compiler
                ->indented('$temp = ')
                ->compileNode($node->getChild('source'))
                ->add(';')
                ->indented('if(empty($temp))')
                ->bracketed($node->getChild('else'))
                ->add(' else')
                ->openBracket()
                ->indented('foreach($temp');
        } else {
            $compiler
                ->indented('foreach(')
                ->compileNode($node->getChild('source'));
        }

        $compiler->add(' as ');

        if ($node->hasChild('loop_key')) {
            $compiler
                ->compileNode($node->getChild('loop_key'))
                ->add(' => ');
        }

        $compiler
            ->compileNode($node->getChild('loop_variable'))
            ->add(') ')
            ->bracketed($node->getChild('loop_body'));

        if ($node->hasChild('else')) {
            //bracket opened in line 75
            $compiler->closeBracket();
        }
        if ($data['save_temp_var']) {
            $compiler->indented('$temp = array_pop($stack);');
            if ($data['create_stack']) {
                $compiler->indented('unset($stack);');
            }
        }
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $elseTest = function (Token $token) {
            if ($token->test(Token::EXPRESSION_START, 'else')) {
                return true;
            }

            return $token->test(Token::TAG, 'endfor');
        };
        $endTest  = function (Token $token) {
            return $token->test(Token::TAG, 'endfor');
        };

        $node = new TagNode($this, array(
            'save_temp_var' => true,
            'create_stack'  => true
        ));

        $loop_var = $parser->parseExpression($stream);
        if ($stream->current()->test(Token::PUNCTUATION, ':')) {
            $node->addChild($loop_var, 'loop_key');
            $loop_var = $parser->parseExpression($stream);
        }

        $stream->expectCurrent(Token::IDENTIFIER, 'in');

        $node->addChild($parser->parseExpression($stream), 'source');
        $node->addChild($loop_var, 'loop_variable');
        $node->addChild($parser->parse($stream, $elseTest), 'loop_body');

        if ($stream->current()->test(Token::EXPRESSION_START, 'else')) {
            $stream->expect(Token::EXPRESSION_END);
            $node->addChild($parser->parse($stream, $endTest), 'else');
        }

        return $node;
    }
}
