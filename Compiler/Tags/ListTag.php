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
use Modules\Templating\Compiler\Tokenizer;

class ListTag extends Tag
{

    public function getTag()
    {
        return 'list';
    }

    public function tokenizeExpression(Tokenizer $tokenizer, $expression)
    {
        $tokenizer->pushToken(Token::EXPRESSION_START, $this->getTag());

        if (!strpos($expression, 'using')) {
            throw new SyntaxException('A template must be specified by the using keyword.');
        }
        list($expression, $template) = explode('using', $expression);

        $tokenizer->tokenizeExpression($template);
        $tokenizer->tokenizeExpression($expression);
        $tokenizer->pushToken(Token::EXPRESSION_END);
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $data               = array();
        $data['template']   = $stream->next()->getValue();
        $data['expression'] = $parser->parseExpression($stream);
        return new TagNode($this, $data);
    }

    public function compile(Compiler $compiler, array $data)
    {
        $compiler
                ->indented('$list_source = ')
                ->compileNode($data['expression'])
                ->add(';');
        $compiler
                ->indented('if(is_array($list_source) || $list_source instanceof \Traversable) {')
                ->indent()
                ->indented('$template = $this->getLoader()->load(')
                ->add($compiler->string($data['template']))
                ->add(');')
                ->indented('foreach ($list_source as $element) {')
                ->indent()
                ->indented('$template->set($element);')
                ->indented('echo $template->render();')
                ->outdent()
                ->indented('}')
                ->outdent()
                ->indented('}');
    }
}
