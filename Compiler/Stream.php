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

    private function testOrThrow(Token $token, $type, $value)
    {
        if ($token->test($type, $value)) {
            return $token;
        }
        $pattern = 'Unexpected %s (%s) found in line %s';
        $message = sprintf($pattern, $token->getTypeString(), $token->getValue(), $token->getLine());
        throw new SyntaxException($message);
    }

    public function expect($type, $value = null)
    {
        $next = $this->next();
        return $this->testOrThrow($next, $type, $value);
    }

    public function expectCurrent($type, $value = null)
    {
        $current = $this->current();
        return $this->testOrThrow($current, $type, $value);
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
