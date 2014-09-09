<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Tags;

use Minty\Compiler\Node;
use Minty\Compiler\Nodes\FunctionNode;
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
        return $this->helper->createRenderBlockNode(
            $stream->expect(Token::IDENTIFIER)->getValue(),
            $this->getContext($parser, $stream)
        );
    }

    /**
     * @param Parser $parser
     * @param Stream $stream
     *
     * @return Node|null
     */
    private function getContext(Parser $parser, Stream $stream)
    {
        if ($stream->next()->test(Token::IDENTIFIER, 'using')) {
            $contextNode = new FunctionNode('createContext');
            $contextNode->addArgument($parser->parseExpression($stream));
            $contextNode->setObject(new TempVariableNode('environment'));

            return $contextNode;
        }

        return null;
    }
}
