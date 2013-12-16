<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Operators\BitwiseOperators;

use Modules\Templating\Compiler\Operators\SimpleBinaryOperator;

class BitwiseXorOperator extends SimpleBinaryOperator
{

    public function operators()
    {
        return 'b-xor';
    }

    protected function compileOperator()
    {
        return ' ^ ';
    }
}
