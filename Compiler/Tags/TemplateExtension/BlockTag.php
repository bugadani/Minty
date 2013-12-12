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
use Modules\Templating\Compiler\Token;

class BlockTag extends Tag
{

    public function hasEndingTag()
    {
        return true;
    }

    public function getTag()
    {
        return 'block';
    }

    public function compile(Compiler $compiler, array $data)
    {
        $compiler->startTemplate($data['template']);
        $data['body']->compile($compiler);
        $template = $compiler->endTemplate();
        $compiler->indented('echo $this->%s();', $template);
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $name = $stream->next()->getValue();
        $stream->expect(Token::EXPRESSION_END);

        $end = function(Stream $stream) {
            return $stream->next()->test(Token::TAG, 'endblock');
        };

        $data = array(
            'template' => $name,
            'body'     => $parser->parse($stream, $end)
        );
        return new TagNode($this, $data);
    }
}
