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

    public function tokenizeExpression(Tokenizer $tokenizer, $expression)
    {
        $tokenizer->pushToken(Token::EXPRESSION_START, $this->getTag());
        list($vars, $source) = explode(' in ', $expression, 2);
        if (strpos($vars, ':') !== false) {
            list($key, $var) = explode(':', $vars, 2);
            $tokenizer->pushToken(Token::IDENTIFIER, trim($key));
            $tokenizer->pushToken(Token::PUNCTUATION, ':');
            $tokenizer->pushToken(Token::IDENTIFIER, trim($var));
        } else {
            $tokenizer->pushToken(Token::IDENTIFIER, trim($vars));
        }
        $tokenizer->pushToken(Token::IDENTIFIER, 'in');
        $tokenizer->tokenizeExpression($source);
        $tokenizer->pushToken(Token::EXPRESSION_END);
    }

    public function compile(Compiler $compiler, array $data)
    {
        $compiler->indented('if (isset($temp)) {');
        $compiler->indent();
        //TODO: save $temp to a temporary stack to support nested for loops
        $compiler
            ->outdent()
            ->indented('}')
            ->indented('$temp = ')
            ->compileNode($data['source'])
            ->add(';');
        if (isset($data['else'])) {
            $compiler
                ->indented('if(empty($temp)) {')
                ->indent()
                ->compileNode($data['else'])
                ->outdent()
                ->indented('} else {')
                ->indent();
        }
        $compiler->indented('foreach($temp as ');
        if ($data['loop_key'] !== null) {
            $compiler
                ->add('$this->' . $data['loop_key'])
                ->add(' => ');
        }
        $compiler->add('$this->' . $data['loop_variable'])
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

        $loop_var = $stream->next()->getValue();
        if ($stream->nextTokenIf(Token::PUNCTUATION, ':')) {
            $key      = $loop_var;
            $loop_var = $stream->next()->getValue();
        } else {
            $key = null;
        }
        $stream->expect(Token::IDENTIFIER, 'in');
        $data = array(
            'loop_variable' => $loop_var,
            'loop_key'      => $key,
            'source'        => $parser->parseExpression($stream),
        );

        $data['loop'] = $parser->parse($stream, $else);

        if ($stream->current()->test(Token::IDENTIFIER, 'else')) {
            $stream->expect(Token::EXPRESSION_END);

            $data['else'] = $parser->parse($stream, $end);
        }

        return new TagNode($this, $data);
    }
}
