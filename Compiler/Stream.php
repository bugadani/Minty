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

    public function __construct(array $tokens)
    {
        array_unshift($tokens, null);
        $this->tokens = $tokens;
        reset($this->tokens);
    }

    public function current()
    {
        return current($this->tokens);
    }

    public function prev()
    {
        return prev($this->tokens);
    }

    public function next()
    {
        return next($this->tokens);
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

    public function expectCurrent($type, $value = null)
    {
        $current = $this->current();
        if ($current->test($type, $value)) {
            return $current;
        }
        $pattern = 'Unexpected %s (%s) found in line %s';
        $message = sprintf($pattern, $current->getTypeString(), $current->getValue(), $current->getLine());
        throw new SyntaxException($message);
    }

    public function nextTokenIf($type, $value = null)
    {
        if ($this->next()->test($type, $value)) {
            return $this->current();
        }
        $this->prev();
        return false;
    }
}
