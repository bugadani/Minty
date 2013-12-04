<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Tags;

use Modules\Templating\Compiler\Parser;
use Modules\Templating\Compiler\Tag;
use Modules\Templating\Compiler\TemplateCompiler;

abstract class Block extends Tag
{

    public function hasEndingTag()
    {
        return true;
    }

    public function compileEndingTag(TemplateCompiler $compiler)
    {
        $compiler->outdent();
        $compiler->output('}');
    }

    public function parseExpression(Parser $parser, $expression)
    {
        $parser->parseExpression($expression);
    }
}
