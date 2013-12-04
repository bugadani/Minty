<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Operators;

use Modules\Templating\Compiler\Operator;
use Modules\Templating\Compiler\Parser;

abstract class ParenthesisOperators extends Operator
{

    public function parseOperator(Parser $parser, $operator)
    {
        if ($operator == $this->operators[0]) {
            return $this->opening($parser, $operator);
        } else {
            return $this->closing($parser, $operator);
        }
    }

    abstract protected function opening(Parser $parser, $operator);

    abstract protected function closing(Parser $parser, $operator);
}
