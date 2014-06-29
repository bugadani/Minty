<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Tags;

use Minty\Compiler\Exceptions\ParseException;
use Minty\Compiler\Parser;
use Minty\Compiler\Stream;
use Minty\Compiler\Tag;

class ExtendsTag extends Tag
{

    public function getTag()
    {
        return 'extends';
    }

    public function parse(Parser $parser, Stream $stream)
    {
        if(!$parser->inMainScope()) {
            throw new ParseException(
                "Extends tags must be placed in the main scope. Unexpected extends tag",
                $stream->current()->getLine()
            );
        }
        $parser->getCurrentClassNode()->setParentTemplate(
            $parser->parseExpression($stream)
        );
    }
}
