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
use Modules\Templating\Compiler\Nodes\TempVariableNode;
use Modules\Templating\Compiler\Parser;
use Modules\Templating\Compiler\Stream;
use Modules\Templating\Compiler\Tag;
use Modules\Templating\Compiler\Token;
use Modules\Templating\Compiler\Tokenizer;

class IncludeTag extends Tag
{

    public function getTag()
    {
        return 'include';
    }

    public function compile(Compiler $compiler, TagNode $node)
    {
        $compiler
            ->indented('$this->getLoader()->render(')
            ->compileNode($node->getChild('template'))
            ->add(', ')
            ->compileNode($node->getChild('arguments'))
            ->add(');');
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $node = new TagNode($this);

        $templateName = $parser->parseExpression($stream);
        if ($stream->current()->test(Token::IDENTIFIER, 'using')) {
            $contextNode = $parser->parseExpression($stream);
        } else {
            $contextNode = new TempVariableNode('context');
        }

        $node->addChild($templateName, 'template');
        $node->addChild($contextNode, 'arguments');

        return $node;
    }
}
