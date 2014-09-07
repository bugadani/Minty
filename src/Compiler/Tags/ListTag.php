<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Tags;

use Minty\Compiler\Compiler;
use Minty\Compiler\Nodes\TagNode;
use Minty\Compiler\Nodes\TempVariableNode;
use Minty\Compiler\Parser;
use Minty\Compiler\Stream;
use Minty\Compiler\Tag;
use Minty\Compiler\Tags\Helpers\MethodNodeHelper;
use Minty\Compiler\Token;

class ListTag extends Tag
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
        return 'list';
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $source = $parser->parseExpression($stream);
        $node   = new TagNode($this);

        if ($stream->current()->test(Token::IDENTIFIER, 'as')) {
            $node->addData(
                'key',
                $stream->expect(Token::VARIABLE)->getValue()
            );

            $stream->next();
        }
        $stream->expectCurrent(Token::IDENTIFIER, 'using');

        $node->addChild(
            $this->helper->createRenderFunctionNode(
                $parser->parseExpression($stream),
                new TempVariableNode('element')
            ),
            'expression'
        );
        $node->addChild($source, 'source');

        return $node;
    }

    public function compile(Compiler $compiler, TagNode $node)
    {
        $compiler
            ->indented('foreach (')
            ->compileNode($node->getChild('source'))
            ->add(' as $element) {')
            ->indent();

        if ($node->hasData('key')) {
            $compiler->indented('$element = ["%s" => $element];', $node->getData('key'));
        }

        $compiler
            ->compileNode($node->getChild('expression'))
            ->outdent()
            ->indented('}');
    }
}
