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
        return new TagNode($this, array(
            'blockName' => $parser->getCurrentBlock()
        ));
    }

    public function compile(Compiler $compiler, TagNode $node)
    {
        $compiler->indented('$this->renderBlock(')
            ->compileString($node->getData('blockName'))
            ->add(', $context, true);');
    }
}
