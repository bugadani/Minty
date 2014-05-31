<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Tags\TemplateExtension;

use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\Nodes\ClassNode;
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
            $node->getData('template')
        );

        if ($node->hasChild('arguments')) {
            $compiler->indented('$embedded->set(');
            $node->getChild('arguments')->compile($compiler);
            $compiler->add(');');
        }

        $compiler->indented('$embedded->render();');
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $fileNode = $parser->getCurrentFileNode();

        /** @var $classNode ClassNode */
        $classNode = $fileNode->addChild(
            new ClassNode(
                $parser->getEnvironment(),
                $fileNode->getNextEmbeddedTemplateName()
            )
        );
        $classNode->setParentTemplate(
            $stream->expect(Token::STRING)->getValue()
        );

        $node = new TagNode($this, array(
            'template' => $classNode->getClassName()
        ));

        $oldClassNode = $parser->getCurrentClassNode();
        $parser->setCurrentClassNode($classNode);
        if ($stream->nextTokenIf(Token::IDENTIFIER, 'using')) {
            $node->addChild($parser->parseExpression($stream), 'arguments');
        }
        $stream->expect(Token::EXPRESSION_END);

        $classNode->addChild(
            $parser->parse(
                $stream,
                function (Token $token) {
                    return $token->test(Token::TAG, 'endembed');
                }
            ),
            'render'
        );
        $parser->setCurrentClassNode($oldClassNode);

        return $node;
    }
}
