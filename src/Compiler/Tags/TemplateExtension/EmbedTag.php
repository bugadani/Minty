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
use Modules\Templating\Compiler\Nodes\TempVariableNode;
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

        $compiler
            ->indented('$embedded->displayTemplate(')
            ->indent()
            ->indented('$this->getEnvironment()->createContext(')
            ->indent()
            ->indented('')
            ->compileNode($node->getChild('arguments'))
            ->outdent()
            ->indented(')')
            ->outdent()
            ->indented(');');
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $fileNode = $parser->getCurrentFileNode();

        /** @var $classNode ClassNode */
        $embeddedClassName = $fileNode->getNextEmbeddedTemplateName();
        $classNode = $fileNode->addChild(
            new ClassNode(
                $parser->getEnvironment(),
                $embeddedClassName,
                $embeddedClassName
            )
        );

        $node = new TagNode($this, array(
            'template' => $classNode->getClassName()
        ));
        $classNode->setParentTemplate($parser->parseExpression($stream));

        $oldClassNode = $parser->getCurrentClassNode();
        $parser->setCurrentClassNode($classNode);
        if ($stream->current()->test(Token::IDENTIFIER, 'using')) {
            $contextNode = $parser->parseExpression($stream);
        } else {
            $contextNode = new TempVariableNode('context');
        }
        $node->addChild($contextNode, 'arguments');
        $stream->expectCurrent(Token::EXPRESSION_END);

        $classNode->addChild(
            $parser->parseBlock($stream, 'endembed'),
            '__main_template_block'
        );
        $parser->setCurrentClassNode($oldClassNode);

        return $node;
    }
}
