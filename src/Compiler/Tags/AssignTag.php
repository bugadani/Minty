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

class AssignTag extends Tag
{
    private $pattern = '/([a-zA-Z]+[a-zA-Z0-9\_]*(?:\[.*?\])?)\s*:\s*(.*?)$/ADsu';

    public function getTag()
    {
        return 'assign';
    }

    public function isPatternBased()
    {
        return true;
    }

    public function matches($tag)
    {
        $match = array();
        if (!preg_match($this->pattern, $tag, $match)) {
            return false;
        }

        return !in_array(
            $match[1],
            array(
                'true',
                'false',
                'null'
            )
        );
    }

    public function tokenize(Tokenizer $tokenizer, $expression)
    {
        $match = array();
        preg_match($this->pattern, $expression, $match);
        list(, $identifier, $expression) = $match;

        parent::tokenize($tokenizer, $identifier);
        parent::tokenize($tokenizer, $expression);
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $name = $parser->parseExpression($stream);
        $stream->expect(Token::EXPRESSION_START);
        $node = $parser->parseExpression($stream);

        return new TagNode($this, array(
            'variable_name' => $name,
            'value_node'    => $node
        ));
    }

    public function compile(Compiler $compiler, array $data)
    {
        $compiler
            ->compileNode($data['variable_name'])
            ->add(' = ')
            ->compileNode($data['value_node'])
            ->add(';');
    }
}
