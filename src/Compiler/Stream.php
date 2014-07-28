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
    /**
     * @var Tokenizer
     */
    private $tokenizer;

    /**
     * @var Token
     */
    private $current;

    /**
     * @var Token
     */
    private $next;

    public function __construct(Tokenizer $tokenizer)
    {
        $this->tokenizer = $tokenizer;
        $this->next();
    }

    /**
     * @return Token
     */
    public function next()
    {
        $this->current = $this->next;
        $this->next    = $this->tokenizer->nextToken();

        return $this->current;
    }

    /**
     * @return Token
     */
    public function current()
    {
        return $this->current;
    }

    /**
     * @param $type
     * @param $value
     *
     * @return Token
     */
    public function expect($type, $value = null)
    {
        $this->next();

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
        $token = $this->current;
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
        $token = $this->next;
        if ($token->test($type, $value)) {
            return $this->next();
        }

        return false;
    }
}
