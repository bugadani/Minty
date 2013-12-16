<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Operators\LogicOperators;

use Modules\Templating\Compiler\Operators\SimpleBinaryOperator;

class XorOperator extends SimpleBinaryOperator
{

    public function operators()
    {
        return 'xor';
    }

    public function compileOperator()
    {
        return ' xor ';
    }
}
