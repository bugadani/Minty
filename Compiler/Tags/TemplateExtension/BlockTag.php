<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Tags\TemplateExtension;

use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\Nodes\RootNode;
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
        $stream->next();
        $name = $stream->current()->getValue();
        $stream->expect(Token::EXPRESSION_END);

        $body = new RootNode();

        $data = array(
            'template' => $name,
            'body'     => $body
        );

        while (!$stream->next()->test(Token::TAG, 'endblock')) {
            $body->addChild($parser->parseToken($stream));
        }
        return new TagNode($this, $data);
    }
}
