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

    public function compile(Compiler $compiler, TagNode $node)
    {
        $compiler
            ->indented('')
            ->compileNode($node->getChild('expression'))
            ->add(';');
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $templateName = $parser->parseExpression($stream);
        if ($stream->current()->test(Token::IDENTIFIER, 'using')) {
            $contextNode = $parser->parseExpression($stream);
        } else {
            $contextNode = new TempVariableNode('context');
        }

        return $this->helper->createRenderFunctionNode($this, $templateName, $contextNode);
    }
}
