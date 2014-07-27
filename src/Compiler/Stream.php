<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler;

use Minty\Compiler\Exceptions\SyntaxException;

class Stream
{
    private $tokens = array(null);

    public function rewind()
    {
        reset($this->tokens);
    }

    public function push(Token $token)
    {
        $this->tokens[] = $token;
    }

    /**
     * @return Token
     */
    public function next()
    {
        return next($this->tokens);
    }

    /**
     * @return Token
     */
    public function current()
    {
        return current($this->tokens);
    }

    /**
     * @param $type
     * @param $value
     *
     * @return Token
     */
    public function expect($type, $value = null)
    {
        next($this->tokens);

        return $this->expectCurrent($type, $value);
    }

    /**
     * @param $type
     * @param $value
     *
     * @throws Exceptions\SyntaxException
     * @return Token
     */
    public function expectCurrent($type, $value = null)
    {
        $token = current($this->tokens);
        if ($token->test($type, $value)) {
            return $token;
        }
        $value   = $token->getValue();
        $message = "Unexpected {$token->getTypeString()}";
        if ($value === true) {
            $message .= ' (true)';
        } elseif ($value === false) {
            $message .= ' (false)';
        } elseif ($value !== '') {
            $message .= " ({$value})";
        }
        throw new SyntaxException($message, $token->getLine());
    }

    /**
     * @param $type
     * @param $value
     *
     * @return bool|Token
     */
    public function nextTokenIf($type, $value = null)
    {
        $token = next($this->tokens);
        if ($token->test($type, $value)) {
            return $token;
        }
        prev($this->tokens);

        return false;
    }
}
