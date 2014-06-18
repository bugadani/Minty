<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler;

use Minty\Compiler\Exceptions\SyntaxException;

class Stream extends \SplDoublyLinkedList
{

    public function __construct(array $tokens = null)
    {
        $this->push(null);
        if (!empty($tokens)) {
            foreach ($tokens as $token) {
                $this->push($token);
            }
            $this->rewind();
        }
    }

    /**
     * @return Token
     */
    public function next()
    {
        parent::next();

        return $this->current();
    }

    /**
     * @param $type
     * @param $value
     *
     * @return Token
     */
    public function expect($type, $value = null)
    {
        parent::next();

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
        $token = $this->current();
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
        parent::next();
        $token = $this->current();
        if ($token->test($type, $value)) {
            return $token;
        }
        $this->prev();

        return false;
    }
}
