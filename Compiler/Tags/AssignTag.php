<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Tags;

use Modules\Templating\Compiler\Tag;
use Modules\Templating\Compiler\TemplateCompiler;

class AssignTag extends Tag
{

    public function getTag()
    {
        return 'set';
    }

    public function compile(TemplateCompiler $compiler)
    {
        $stream = $compiler->getTokenStream();
        $var    = $stream->nextToken()->getValue();
        $stream->nextToken();
        $expr   = $compiler->compileExpression($stream->nextToken());
        $compiler->output('$this->%s = %s;', $var, $expr);
    }
}
