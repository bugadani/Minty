<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler;

abstract class Tag
{

    abstract public function getTag();

    public function hasEndingTag()
    {
        return false;
    }

    public function setExpectations(TokenStream $stream)
    {

    }

    public function parseExpression(Parser $parser, $expression)
    {
        $parser->parseExpression($expression, '(', ')');
    }

    public function requiresState()
    {
        return array();
    }

    public function getParentTemplate()
    {
        return false;
    }

    abstract public function compile(TemplateCompiler $compiler);
}
