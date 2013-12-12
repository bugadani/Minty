<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler;

use Modules\Templating\Compiler\Exceptions\SyntaxException;

class Stream
{
    /**
     * @var Token[]
     */
    private $tokens;
    private $pointer;

    public function __construct(array $tokens)
    {
        $this->tokens  = $tokens;
        $this->pointer = -1;
    }

    public function current()
    {
        return $this->tokens[$this->pointer];
    }

    public function step($step = 1)
    {
        $this->pointer += $step;
    }

    public function next()
    {
        $this->step();
        return $this->current();
    }

    public function expect($type, $value = null)
    {
        $next = $this->next();
        if ($next->test($type, $value)) {
            return $next;
        }
        $pattern = 'Unexpected %s (%s) found in line %s';
        $message = sprintf($pattern, $next->getTypeString(), $next->getValue(), $next->getLine());
        throw new SyntaxException($message);
    }

    public function nextTokenIf($type, $value = null)
    {
        if ($this->next()->test($type, $value)) {
            return $this->current();
        }
        $this->step(-1);
        return false;
    }
}
