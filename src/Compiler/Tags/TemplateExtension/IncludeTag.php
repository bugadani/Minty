<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Tags\TemplateExtension;

use Minty\Compiler\Compiler;
use Minty\Compiler\Nodes\FunctionNode;
use Minty\Compiler\Nodes\TagNode;
use Minty\Compiler\Nodes\TempVariableNode;
use Minty\Compiler\Parser;
use Minty\Compiler\Stream;
use Minty\Compiler\Tag;
use Minty\Compiler\Token;

class IncludeTag extends Tag
{

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

        $functionNode = new FunctionNode('render', array(
            $templateName,
            $contextNode
        ));
        $functionNode->setObject(new TempVariableNode('environment'));

        $node = new TagNode($this);
        $node->addChild($functionNode, 'expression');

        return $node;
    }
}
