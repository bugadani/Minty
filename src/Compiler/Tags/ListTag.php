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

class ListTag extends Tag
{

    public function getTag()
    {
        return 'list';
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $node = new TagNode($this);

        $node->addChild($parser->parseExpression($stream), 'expression');
        $stream->expectCurrent(Token::IDENTIFIER, 'using');
        $node->addChild($parser->parseExpression($stream), 'template');
        $stream->expectCurrent(Token::EXPRESSION_END);

        return $node;
    }

    public function compile(Compiler $compiler, TagNode $node)
    {
        $compiler
            ->indented('$list_source = ')
            ->compileNode($node->getChild('expression'))
            ->add(';');

        $compiler
            ->indented('if(is_array($list_source) || $list_source instanceof \Traversable) {')
            ->indent()
            ->indented('$template = $this->getLoader()->load(')
            ->compileNode($node->getChild('template'))
            ->add(');')
            ->indented('foreach ($list_source as $element) {')
            ->indent()
            ->indented('$template->clean();')
            ->indented('$template->loadGlobals();')
            ->indented('$template->set($element);')
            ->indented('$template->render();')
            ->outdent()
            ->indented('}')
            ->outdent()
            ->indented('}');
    }
}
