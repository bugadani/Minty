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

class ElseIfTag extends Tag
{
    public function getTag()
    {
        return 'elseif';
    }

    public function parse(Parser $parser, Stream $stream)
    {

    }

    public function compile(Compiler $compiler, TagNode $data)
    {

    }
}
