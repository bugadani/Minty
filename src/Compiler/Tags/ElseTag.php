<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Tags;

use Modules\Templating\Compiler\Token;
use Modules\Templating\Compiler\Tokenizer;

class ElseTag extends MetaTag
{
    public function getTag()
    {
        return 'else';
    }

    public function tokenize(Tokenizer $tokenizer, $expression)
    {
        $tokenizer->pushToken(Token::EXPRESSION_START, 'else');
        $tokenizer->pushToken(Token::EXPRESSION_END);
    }
}
