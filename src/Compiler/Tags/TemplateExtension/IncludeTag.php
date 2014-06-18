<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Tags\TemplateExtension;

use Minty\Compiler\Compiler;
use Minty\Compiler\Nodes\TagNode;
use Minty\Compiler\Nodes\TempVariableNode;
use Minty\Compiler\Parser;
use Minty\Compiler\Stream;
use Minty\Compiler\Tag;
use Minty\Compiler\Token;

class IncludeTag extends Tag
{

    public function getTag()
    {
        return 'include';
    }

    public function compile(Compiler $compiler, TagNode $node)
    {
        $compiler
            ->indented('$this->getEnvironment()->render(')
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
