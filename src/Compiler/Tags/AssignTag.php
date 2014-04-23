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

class AssignTag extends Tag
{

    public function getTag()
    {
        return 'assign';
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $name = $stream->current()->getValue();
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
            ->indented('$this->%s = ', $data['variable_name'])
            ->compileNode($data['value_node'])
            ->add(';');
    }
}