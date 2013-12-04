<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Tags;

use Modules\Templating\Compiler\Tag;
use Modules\Templating\Compiler\TemplateCompiler;

class ParentTag extends Tag
{

    public function getTag()
    {
        return 'parent';
    }

    public function requiresState()
    {
        return array(
                //array(Parser::STATE_BLOCK, 'template'),
                //array(Parser::STATE_BLOCK, 'block')
        );
    }

    public function compile(TemplateCompiler $compiler)
    {
        $compiler->output('echo parent::%s();', $compiler->getCurrentTemplate());
    }
}
