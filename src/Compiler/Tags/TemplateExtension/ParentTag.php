<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Tags\TemplateExtension;

use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\Nodes\TagNode;
use Modules\Templating\Compiler\Parser;
use Modules\Templating\Compiler\Stream;
use Modules\Templating\Compiler\Tag;
use Modules\Templating\Compiler\Tokenizer;

class ParentTag extends Tag
{

    public function getTag()
    {
        return 'parent';
    }

    public function tokenizeExpression(Tokenizer $tokenizer, $expression)
    {

    }

    public function parse(Parser $parser, Stream $stream)
    {
        $stream->prev();

        return new TagNode($this);
    }

    public function compile(Compiler $compiler, array $data)
    {
        $compiler->indented('echo parent::%s();', $compiler->getCurrentTemplate());
    }
}
