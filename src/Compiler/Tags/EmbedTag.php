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
use Minty\Compiler\Nodes\TagNode;
use Minty\Compiler\Nodes\TempVariableNode;
use Minty\Compiler\Parser;
use Minty\Compiler\Stream;
use Minty\Compiler\Tag;
use Minty\Compiler\Tags\Helpers\MethodNodeHelper;
use Minty\Compiler\Token;

class EmbedTag extends Tag
{
    /**
     * @var MethodNodeHelper
     */
    private $helper;

    public function __construct(MethodNodeHelper $helper)
    {
        $this->helper = $helper;
    }

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
            ->indented('(new ' . $node->getData('template'))
            ->add('(')
            ->compileNode($node->getChild('environment'))
            ->add('))->displayTemplate(')
            ->compileNode($node->getChild('context'))
            ->add(');');
    }

    public function parse(Parser $parser, Stream $stream)
    {
        //force the optimizer to compile $environment by always using a temp variable
        $environmentNode = new TempVariableNode('environment');

        $parentTemplate = $parser->parseExpression($stream);

        $contextNode = $this->helper->createContext(
            $stream->current()->test(Token::IDENTIFIER, 'using'),
            $stream,
            $parser
        );

        $node = new TagNode(
            $this, [
                'template' => $this->parseClass($parser, $stream, $parentTemplate)
            ]
        );

        $node->addChild($environmentNode, 'environment');
        $node->addChild($contextNode, 'context');

        return $node;
    }

    /**
     * @param Parser $parser
     * @param Stream $stream
     * @param        $parentTemplate
     *
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
        $stream->expect(Token::TAG_END);
        $parser->setCurrentClassNode($oldClassNode);

        return $classNode->getClassName();
    }
}
