<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler;

use Modules\Templating\Compiler\Exceptions\SyntaxException;

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
    public function prev()
    {
        parent::prev();

        return $this->current();
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
        $type  = $token->getTypeString();
        $value = $token->getValue();
        $line  = $token->getLine();
        throw new SyntaxException("Unexpected {$type} ({$value}) found in line {$line}");
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
        parent::prev();

        return false;
    }
}
