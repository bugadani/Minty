<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Tags;

use Minty\Compiler\Nodes\DataNode;
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
        $node = $this->helper->createRenderBlockNode($parser->getCurrentBlock());
        $node->getChild('expression')->addArgument(new DataNode(true));
        $stream->expect(Token::TAG_END);

        return $node;
    }
}
