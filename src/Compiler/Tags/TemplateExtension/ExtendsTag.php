<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Tags\TemplateExtension;

use Modules\Templating\Compiler\Parser;
use Modules\Templating\Compiler\Stream;
use Modules\Templating\Compiler\Tag;

class ExtendsTag extends Tag
{

    public function getTag()
    {
        return 'extends';
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $parser->getCurrentClassNode()->setParentTemplate(
            $parser->parseExpression($stream)
        );
    }
}
