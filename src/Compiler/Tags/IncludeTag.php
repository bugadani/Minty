<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Tags;

use Minty\Compiler\Nodes\TempVariableNode;
use Minty\Compiler\Parser;
use Minty\Compiler\Stream;
use Minty\Compiler\Tag;
use Minty\Compiler\Tags\Helpers\MethodNodeHelper;
use Minty\Compiler\Token;

class IncludeTag extends Tag
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
        return 'include';
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $templateName = $parser->parseExpression($stream);
        if ($stream->current()->test(Token::IDENTIFIER, 'using')) {
            $contextNode = $parser->parseExpression($stream);
        } else {
            $contextNode = new TempVariableNode('context');
        }

        return $this->helper->createRenderFunctionNode($templateName, $contextNode);
    }
}
