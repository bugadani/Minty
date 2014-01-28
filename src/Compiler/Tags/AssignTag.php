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
use Modules\Templating\Compiler\Token;
use Modules\Templating\Compiler\Stream;

class AssignTag extends Tag
{

    public function getTag()
    {
        return 'assign';
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $data                  = array();
        $data['variable_name'] = $stream->current()->getValue();
        $stream->expect(Token::EXPRESSION_START);
        $data['value_node']    = $parser->parseExpression($stream);
        return new TagNode($this, $data);
    }

    public function compile(Compiler $compiler, array $data)
    {
        $var        = $data['variable_name'];
        $value_node = $data['value_node'];

        $compiler
                ->indented('$this->%s = ', $var)
                ->compileNode($value_node)
                ->add(';');
    }
}
