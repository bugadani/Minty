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
use Minty\Compiler\Parser;
use Minty\Compiler\Stream;
use Minty\Compiler\Tag;
use Minty\Compiler\Token;

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

    public function compile(Compiler $compiler, TagNode $node)
    {
        $compiler->indented('$this->renderBlock(')
            ->compileString($node->getData('template'))
            ->add(', $context);');
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $templateName = $stream->expect(Token::IDENTIFIER)->getValue();
        $stream->expect(Token::EXPRESSION_END);

        $parser->enterBlock($templateName);
        $parser->getCurrentClassNode()->addChild(
            $parser->parseBlock($stream, 'endblock'),
            $templateName
        );
        $parser->leaveBlock();

        return new TagNode($this, array(
            'template' => $templateName
        ));
    }
}
