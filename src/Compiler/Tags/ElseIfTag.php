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

class ElseIfTag extends MetaTag
{
    public function getTag()
    {
        return 'elseif';
    }
}
