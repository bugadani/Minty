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
        if ($this->current->test($type, $value)) {
            return $this->current;
        }
        $value   = $this->current->getValue();
        $message = "Unexpected { $this->current->getTypeString()}";
        if ($value === true) {
            $message .= ' (true)';
        } elseif ($value === false) {
            $message .= ' (false)';
        } elseif ($value !== '') {
            $message .= " ({$value})";
        }
        throw new SyntaxException($message, $this->current->getLine());
    }

    /**
     * @param $type
     * @param $value
     *
     * @return bool|Token
     */
    public function nextTokenIf($type, $value = null)
    {
        if ($this->next->test($type, $value)) {
            return $this->next();
        }

        return false;
    }
}
