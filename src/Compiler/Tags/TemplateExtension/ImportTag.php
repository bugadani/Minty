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
use Minty\Compiler\Nodes\VariableNode;
use Minty\Compiler\Parser;
use Minty\Compiler\Stream;
use Minty\Compiler\Tag;
use Minty\Compiler\Tags\Helpers\MethodNodeHelper;
use Minty\Compiler\Token;

class ImportTag extends Tag
{
    /**
     * @var MethodNodeHelper
     */
    private $helper;

    public function __construct(MethodNodeHelper $helper)
    {
        $this->helper = $helper;
    }

    public function getTag()
    {
        return 'import';
    }

    public function compile(Compiler $compiler, TagNode $node)
    {
        $compiler
            ->indented('')
            ->compileNode($node->getChild('expression'))
            ->add(';');
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $node = new TagNode($this);
        if ($stream->nextTokenIf(Token::IDENTIFIER, 'all')) {
            $stream->expect(Token::IDENTIFIER, 'from');
        } else {
            $blocks = $parser->parseExpression($stream);
            $stream->expectCurrent(Token::IDENTIFIER, 'from');
        }
        $functionNode = $this->helper->createMethodCallNode(
            new VariableNode('_self'),
            'importBlocks'
        );
        $functionNode->addArgument(
            $parser->parseExpression($stream)
        );
        if (isset($blocks)) {
            $functionNode->addArgument($blocks);
        }

        $node->addChild($functionNode, 'expression');

        return $node;
    }
}
