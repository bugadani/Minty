<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Tags;

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
            $this->helper->createContext(
                $stream->next()->test(Token::IDENTIFIER, 'using'),
                $stream,
                $parser
            )
        );
    }
}
