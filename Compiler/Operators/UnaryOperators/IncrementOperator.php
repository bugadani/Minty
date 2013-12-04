<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Operators\UnaryOperators;

use Modules\Templating\Compiler\Operators\UnaryOperators;
use Modules\Templating\Compiler\Parser;
use Modules\Templating\Compiler\Token;

class IncrementOperator extends UnaryOperators
{

    public function operators()
    {
        return '++';
    }

    public function pre(Parser $parser, $operator)
    {
        parent::pre($parser, $operator);

        $stream = $parser->getTokenStream();
        $stream->expect(Token::IDENTIFIER)->then(Token::ARGUMENT_LIST_START, 'args', 1, true);
        return true;
    }
}
