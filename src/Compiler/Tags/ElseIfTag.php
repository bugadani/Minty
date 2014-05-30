<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Tags;

class ElseIfTag extends MetaTag
{
    public function getTag()
    {
        return 'elseif';
    }
}
