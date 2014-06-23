<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Tags\TemplateExtension;

use Minty\Compiler\Compiler;
use Minty\Compiler\Nodes\ClassNode;
use Minty\Compiler\Nodes\FunctionNode;
use Minty\Compiler\Nodes\TagNode;
use Minty\Compiler\Nodes\TempVariableNode;
use Minty\Compiler\Parser;
use Minty\Compiler\Stream;
use Minty\Compiler\Tag;
use Minty\Compiler\Token;

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
            '$embedded = new %s($environment);',
            $node->getData('template')
        );

        $compiler
            ->indented('')
            ->compileNode($node->getChild('display'))
            ->add(';');
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $fileNode = $parser->getCurrentFileNode();

        /** @var $classNode ClassNode */
        $embeddedClassName = $fileNode->getNextEmbeddedTemplateName();
        $classNode         = $fileNode->addChild(
            new ClassNode(
                $parser->getEnvironment(),
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
        $stream->expectCurrent(Token::EXPRESSION_END);

        $classNode->addChild(
            $parser->parseBlock($stream, 'endembed'),
            '__main_template_block'
        );
        $parser->setCurrentClassNode($oldClassNode);

        $createContextNode = new FunctionNode('createContext', array($contextNode));
        $functionNode      = new FunctionNode('displayTemplate', array($createContextNode));

        $createContextNode->setObject(new TempVariableNode('environment'));
        $functionNode->setObject(new TempVariableNode('embedded'));

        $node->addChild($functionNode, 'display');

        return $node;
    }
}
