<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Tags;

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
        $compiler
            ->indented('$embedded = new ' . $node->getData('template'))
            ->add('(')
            ->compileNode($node->getChild('environment'))
            ->add(');');

        $compiler
            ->indented('')
            ->compileNode($node->getChild('display'))
            ->add(';');
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

        $node = new TagNode($this, array(
            'template' => $classNode->getClassName()
        ));

        //force the optimizer to compile $environment
        $environmentNode = new TempVariableNode('environment');
        $classNode->setParentTemplate($parser->parseExpression($stream));

        $oldClassNode = $parser->getCurrentClassNode();
        $parser->setCurrentClassNode($classNode);
        if ($stream->current()->test(Token::IDENTIFIER, 'using')) {
            $contextNode = $parser->parseExpression($stream);
            $contextNode = new FunctionNode('createContext', array($contextNode));
            $contextNode->setObject($environmentNode);
        } else {
            $contextNode = new TempVariableNode('context');
        }
        $stream->expectCurrent(Token::EXPRESSION_END);

        $classNode->addChild(
            $parser->parseBlock($stream, 'endembed'),
            ClassNode::MAIN_TEMPLATE_BLOCK
        );
        $parser->setCurrentClassNode($oldClassNode);

        $functionNode = new FunctionNode('displayTemplate', array($contextNode));
        $functionNode->setObject(new TempVariableNode('embedded'));

        $node->addChild($environmentNode, 'environment');
        $node->addChild($functionNode, 'display');

        return $node;
    }
}
