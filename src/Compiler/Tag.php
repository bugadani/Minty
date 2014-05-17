<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler;

use Modules\Templating\Compiler\Nodes\TagNode;

abstract class Tag
{

    abstract public function getTag();

    public function hasEndingTag()
    {
        return false;
    }

    public function isPatternBased()
    {
        return false;
    }

    public function matches($tag)
    {
        return false;
    }

    public function addNameToken(Tokenizer $tokenizer)
    {
        $tokenizer->pushToken(Token::TAG, $this->getTag());
    }

    public function tokenize(Tokenizer $tokenizer, $expression)
    {
        $tokenizer->pushToken(Token::EXPRESSION_START, $this->getTag());
        $tokenizer->tokenizeExpression($expression);
        $tokenizer->pushToken(Token::EXPRESSION_END);
    }

    abstract public function parse(Parser $parser, Stream $stream);

    abstract public function compile(Compiler $compiler, TagNode $data);
}
