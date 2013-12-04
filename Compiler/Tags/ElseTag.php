<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Tags;

use Modules\Templating\Compiler\Parser;
use Modules\Templating\Compiler\Tag;
use Modules\Templating\Compiler\TemplateCompiler;

class ElseTag extends Tag
{

    public function getTag()
    {
        return 'else';
    }

    public function requiresState()
    {
        return array(
            array(Parser::STATE_BLOCK, 'switch'),
            array(Parser::STATE_BLOCK, 'if'),
            array(Parser::STATE_BLOCK, 'for'),
        );
    }

    public function compile(TemplateCompiler $compiler)
    {
        $state = $compiler->getState();
        switch ($state) {
            case TemplateCompiler::STATE_BLOCK_IF:
                $compiler->outdent();
                $compiler->output('} else {');
                $compiler->indent();
                break;

            case TemplateCompiler::STATE_BLOCK_SWITCH_HAS_CASE:
                $compiler->output('break;');
                $compiler->output('');
                $compiler->outdent();
            /* intentional */

            case TemplateCompiler::STATE_BLOCK_SWITCH:
                $compiler->output('default:');
                $compiler->indent();
                break;

            case TemplateCompiler::STATE_BLOCK_FOR:
                $compiler->outdent();
                $compiler->output('} if($this->isEmpty(%s)) {', $compiler->getStateData());
                $compiler->indent();
                break;
        }
    }
}
