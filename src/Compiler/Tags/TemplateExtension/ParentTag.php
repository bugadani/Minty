<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Tags\TemplateExtension;

use Minty\Compiler\Compiler;
use Minty\Compiler\Nodes\DataNode;
use Minty\Compiler\Nodes\FunctionNode;
use Minty\Compiler\Nodes\TagNode;
use Minty\Compiler\Nodes\TempVariableNode;
use Minty\Compiler\Nodes\VariableNode;
use Minty\Compiler\Parser;
use Minty\Compiler\Stream;
use Minty\Compiler\Tag;

class ParentTag extends Tag
{

    public function getTag()
    {
        return 'parent';
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $functionNode = new FunctionNode('renderBlock', array(
            new DataNode($parser->getCurrentBlock()),
            new TempVariableNode('context'),
            new DataNode(true)
        ));
        $functionNode->setObject(new VariableNode('_self'));

        $node = new TagNode($this);
        $node->addChild($functionNode, 'expression');

        return $node;
    }

    public function compile(Compiler $compiler, TagNode $node)
    {
        $compiler
            ->indented('')
            ->compileNode($node->getChild('expression'))
            ->add(';');
    }
}
