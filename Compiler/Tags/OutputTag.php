<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Tags;

use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\Parser;
use Modules\Templating\Compiler\Tag;
use Modules\Templating\Compiler\Nodes\TagNode;
use Modules\Templating\Compiler\Stream;

class OutputTag extends Tag
{

    public function getTag()
    {
        return 'output';
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $data               = array();
        $data['expression'] = $parser->parseExpression($stream);
        return new TagNode($this, $data);
    }

    public function compile(Compiler $compiler, array $data)
    {
        $compiler->indented('echo ');
        $compiler->add($compiler->compileNode($data['expression']));
        $compiler->add(';');
    }
}
