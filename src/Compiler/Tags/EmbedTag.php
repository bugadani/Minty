<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Tags;

use Minty\Compiler\Compiler;
use Minty\Compiler\Node;
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
        //force the optimizer to compile $environment by always using a temp variable
        $environmentNode = new TempVariableNode('environment');

        $parentTemplate = $parser->parseExpression($stream);

        $functionNode = new FunctionNode('displayTemplate');
        $functionNode->setObject(new TempVariableNode('embedded'));
        $functionNode->addArgument(
            $this->getContext($parser, $stream, $environmentNode)
        );

        $node = new TagNode($this, array(
            'template' => $this->parseClass($parser, $stream, $parentTemplate)
        ));

        $node->addChild($environmentNode, 'environment');
        $node->addChild($functionNode, 'display');

        return $node;
    }

    /**
     * @param Parser $parser
     * @param Stream $stream
     * @param        $parentTemplate
     * @return ClassNode
     */
    private function parseClass(Parser $parser, Stream $stream, $parentTemplate)
    {
        $oldClassNode = $parser->getCurrentClassNode();
        $fileNode     = $parser->getCurrentFileNode();

        /** @var $classNode ClassNode */
        $classNode = $fileNode->addClass(
            $fileNode->getNextEmbeddedTemplateName()
        );
        $classNode->setParentTemplate($parentTemplate);
        $parser->setCurrentClassNode($classNode);

        $classNode->addChild(
            $parser->parseBlock($stream, 'endembed'),
            ClassNode::MAIN_TEMPLATE_BLOCK
        );
        $parser->setCurrentClassNode($oldClassNode);

        return $classNode->getClassName();
    }

    /**
     * @param Parser $parser
     * @param Stream $stream
     * @param        $environmentNode
     * @return Node
     */
    private function getContext(Parser $parser, Stream $stream, $environmentNode)
    {
        if ($stream->current()->test(Token::IDENTIFIER, 'using')) {
            $contextNode = new FunctionNode('createContext');
            $contextNode->addArgument($parser->parseExpression($stream));
            $contextNode->setObject($environmentNode);
        } else {
            $contextNode = new TempVariableNode('context');
        }

        return $contextNode;
    }
}
