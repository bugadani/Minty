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

    public function compile(Compiler $compiler, TagNode $node)
    {
        $compiler->indented('$this->renderBlock(')
            ->compileString($node->getData('template'))
            ->add(', $context);');
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $templateName = $stream->expect(Token::IDENTIFIER)->getValue();
        $methodName   = $templateName . 'Block';
        $stream->expect(Token::EXPRESSION_END);

        $parser->enterBlock($templateName);
        $parser->getCurrentClassNode()->addChild(
            $parser->parse(
                $stream,
                function (Token $token) {
                    return $token->test(Token::TAG, 'endblock');
                }
            ),
            $methodName
        );
        $parser->leaveBlock();

        return new TagNode($this, array(
            'template' => $templateName
        ));
    }
}
