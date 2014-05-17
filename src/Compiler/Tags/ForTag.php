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

    public function compile(Compiler $compiler, array $data)
    {
        if ($data['save_temp_var']) {
            $compiler
                ->indented('if (isset($temp)) {')
                ->indent();

            if ($data['create_stack']) {
                $compiler->indented('$stack = array();');
            }

            $compiler
                ->indented('$stack[] = $temp;')
                ->outdent()
                ->indented('}');
        }

        if (isset($data['else']) && count($data['else']->getChildren()) > 0) {
            $compiler
                ->indented('$temp = ')
                ->compileNode($data['source'])
                ->add(';')
                ->indented('if(empty($temp)) {')
                ->indent()
                ->compileNode($data['else'])
                ->outdent()
                ->indented('} else {')
                ->indent()
                ->indented('foreach($temp as ');
        } else {
            $compiler->indented('foreach(')
                ->compileNode($data['source'])
                ->add(' as ');
        }

        if ($data['loop_key'] !== null) {
            $compiler
                ->compileNode($data['loop_key'])
                ->add(' => ');
        }

        $compiler
            ->compileNode($data['loop_variable'])
            ->add(') {')
            ->indent()
            ->compileNode($data['loop'])
            ->outdent()
            ->indented('}');

        if (isset($data['else'])) {
            $compiler
                ->outdent()
                ->indented('}');
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
        $else = function (Stream $stream) {
            $token = $stream->next();
            if ($token->test(Token::EXPRESSION_START)) {
                return $stream->nextTokenIf(Token::IDENTIFIER, 'else');
            }

            return $token->test(Token::TAG, 'endfor');
        };
        $end  = function (Stream $stream) {
            return $stream->next()->test(Token::TAG, 'endfor');
        };

        $loop_var = $parser->parseExpression($stream);
        if ($stream->current()->test(Token::PUNCTUATION, ':')) {
            $key      = $loop_var;
            $loop_var = $parser->parseExpression($stream);
        } else {
            $key = null;
        }

        $stream->expectCurrent(Token::IDENTIFIER, 'in');
        $node = new TagNode($this, array(
            'loop_variable' => $loop_var,
            'loop_key'      => $key,
            'save_temp_var' => true,
            'create_stack'  => true,
            'source'        => $parser->parseExpression($stream),
        ));

        $bodyNode = $parser->parse($stream, $else);
        $bodyNode->setParent($node);
        $node->addData('loop', $bodyNode);

        if ($stream->current()->test(Token::IDENTIFIER, 'else')) {
            $stream->expect(Token::EXPRESSION_END);

            $elseNode = $parser->parse($stream, $end);
            $elseNode->setParent($node);
            $node->addData('else', $elseNode);
        }

        return $node;
    }
}
