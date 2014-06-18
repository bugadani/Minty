<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Nodes;

use Minty\Compiler\Compiler;
use Minty\Compiler\Node;

class FileNode extends RootNode
{
    private $embeddedTemplates = 0;

    public function addChild(Node $node, $key = null)
    {
        if (!$node instanceof ClassNode) {
            throw new \InvalidArgumentException('FileNode expects only ClassNode children');
        }

        return parent::addChild($node, $key);
    }

    public function compile(Compiler $compiler)
    {
        $compiler->add("<?php\n");

        /** @var $childNode ClassNode */
        $childNode = $this->getChild(0);

        if ($childNode->getNameSpace() !== '') {
            $compiler->indented("namespace %s;\n", $childNode->getNameSpace());
        }

        $compiler->indented('use Minty\\Environment;');
        $compiler->indented('use Minty\\Context;');

        $compiler->add("\n");

        parent::compile($compiler);
    }

    public function getNextEmbeddedTemplateName()
    {
        return 'embeddedTemplate' . $this->embeddedTemplates++;
    }

}
