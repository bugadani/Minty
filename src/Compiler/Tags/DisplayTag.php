<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Tags;

use Minty\Compiler\Compiler;
use Minty\Compiler\Nodes\FunctionNode;
use Minty\Compiler\Nodes\TagNode;
use Minty\Compiler\Nodes\TempVariableNode;
use Minty\Compiler\Parser;
use Minty\Compiler\Stream;
use Minty\Compiler\Tag;
use Minty\Compiler\Tags\Helpers\MethodNodeHelper;
use Minty\Compiler\Token;

class DisplayTag extends Tag
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
        return 'display';
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $templateName = $stream->expect(Token::IDENTIFIER)->getValue();
        if ($stream->next()->test(Token::IDENTIFIER, 'using')) {
            $contextNode = $parser->parseExpression($stream);
            $contextNode = new FunctionNode('createContext', array($contextNode));
            $contextNode->setObject(new TempVariableNode('environment'));
        } else {
            $contextNode = new TempVariableNode('context');
        }

        return $this->helper->createRenderBlockNode($this, $templateName, $contextNode);
    }

    public function compile(Compiler $compiler, TagNode $node)
    {
        $compiler
            ->indented('')
            ->compileNode($node->getChild('expression'))
            ->add(';');
    }
}
