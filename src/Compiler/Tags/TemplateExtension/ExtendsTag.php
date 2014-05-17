<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Tags\TemplateExtension;

use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\Exceptions\ParseException;
use Modules\Templating\Compiler\Nodes\TagNode;
use Modules\Templating\Compiler\Parser;
use Modules\Templating\Compiler\Stream;
use Modules\Templating\Compiler\Tag;
use Modules\Templating\Compiler\Token;

class ExtendsTag extends Tag
{

    public function getTag()
    {
        return 'extends';
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $node = new TagNode($this);
        if (!$stream->expect(Token::STRING)) {
            throw new ParseException('Extends tag requires a string parameter');
        }
        $node->addData('template', $stream->current()->getValue());
        $stream->expect(Token::EXPRESSION_END);

        return $node;
    }

    public function compile(Compiler $compiler, TagNode $node)
    {
        $data = $node->getData();
        $compiler->setExtendedTemplate($data['template']);
    }
}
