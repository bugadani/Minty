<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Tags;

use Minty\Compiler\Nodes\DataNode;
use Minty\Compiler\Nodes\FunctionNode;
use Minty\Compiler\Parser;
use Minty\Compiler\Stream;
use Minty\Compiler\Tag;
use Minty\Compiler\Tags\Helpers\MethodNodeHelper;
use Minty\Compiler\Token;

class ParentTag extends Tag
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
        return 'parent';
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $node       = $this->helper->createRenderBlockNode(
            $parser->getCurrentBlock(),
            $this->helper->createContext(
                $stream->next()->test(Token::IDENTIFIER, 'using'),
                $stream,
                $parser
            )
        );
        $expression = $node->getChild('expression');
        if (!$expression instanceof FunctionNode) {
            throw new \UnexpectedValueException("An instance of FunctionNode was expected");
        }

        $expression->addArgument(new DataNode(true));

        return $node;
    }
}
