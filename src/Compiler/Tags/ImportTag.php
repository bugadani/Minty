<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Tags;

use Minty\Compiler\Nodes\ExpressionNode;
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

    public function parse(Parser $parser, Stream $stream)
    {
        if ($stream->nextTokenIf(Token::IDENTIFIER, 'all')) {
            $stream->expect(Token::IDENTIFIER, 'from');
        } else {
            $blocks = $parser->parseExpression($stream);
            $stream->expectCurrent(Token::IDENTIFIER, 'from');
        }

        $arguments = array(
            $parser->parseExpression($stream)
        );
        if (isset($blocks)) {
            $arguments[] = $blocks;
        }

        return new ExpressionNode(
            $this->helper->createMethodCallNode(
                new VariableNode('_self'),
                'importBlocks',
                $arguments
            )
        );
    }
}
