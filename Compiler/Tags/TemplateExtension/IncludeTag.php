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

class IncludeTag extends Tag
{

    public function getTag()
    {
        return 'include';
    }

    public function compile(Compiler $compiler, array $data)
    {
        $compiler
                ->indented('$template = $this->getLoader()->load(')
                ->add($compiler->string($data['template']))
                ->add(');')
                ->indented('$template->set(')
                ->compileData($data['arguments'])
                ->add(');')
                ->indented('$template->render();');
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $name = $stream->expect(Token::STRING)->getValue();

        if ($stream->nextTokenIf(Token::IDENTIFIER, 'using')) {
            $arguments = $parser->parseExpression($stream);
        } else {
            $arguments = array();
        }

        $data = array(
            'template'  => $name,
            'arguments' => $arguments
        );

        return new TagNode($this, $data);
    }
}
