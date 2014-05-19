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

class EmbedTag extends Tag
{

    public function hasEndingTag()
    {
        return true;
    }

    public function getTag()
    {
        return 'embed';
    }

    public function compile(Compiler $compiler, TagNode $node)
    {
        $compiler->indented(
            '$embedded = new %s($this->getLoader(), $this->getEnvironment());',
            $compiler->addEmbedded($node->getData('template'), $node->getChild('body'))
        );

        if ($node->hasData('arguments')) {
            $compiler->indented('$embedded->set(')
                ->compileData($node->getData('arguments'))
                ->add(');');
        }

        $compiler->indented('$embedded->render();');
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $node = new TagNode($this, array(
            'template' => $stream->expect(Token::STRING)->getValue()
        ));

        if ($stream->nextTokenIf(Token::IDENTIFIER, 'using')) {
            $node->addData('arguments', $parser->parseExpression($stream));
        }

        $stream->expect(Token::EXPRESSION_END);

        $node->addChild(
            $parser->parse(
                $stream,
                function (Stream $stream) {
                    return $stream->next()->test(Token::TAG, 'endembed');
                }
            ),
            'body'
        );

        return $node;
    }
}
