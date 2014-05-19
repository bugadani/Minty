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

    /**
     * @return Token
     */
    public function current()
    {
        return current($this->tokens);
    }

    /**
     * @return Token
     */
    public function prev()
    {
        return prev($this->tokens);
    }

    /**
     * @return Token
     */
    public function next()
    {
        return next($this->tokens);
    }

    /**
     * @param Token $token
     * @param       $type
     * @param       $value
     *
     * @throws SyntaxException
     * @return Token
     */
    private function testOrThrow(Token $token, $type, $value)
    {
        if ($token->test($type, $value)) {
            return $token;
        }
        throw new SyntaxException(sprintf(
            'Unexpected %s (%s) found in line %s',
            $token->getTypeString(),
            $token->getValue(),
            $token->getLine()
        ));
    }

    /**
     * @param $type
     * @param $value
     *
     * @return Token
     */
    public function expect($type, $value = null)
    {
        $next = next($this->tokens);

        return $this->testOrThrow($next, $type, $value);
    }

    /**
     * @param $type
     * @param $value
     *
     * @return Token
     */
    public function expectCurrent($type, $value = null)
    {
        $current = current($this->tokens);

        return $this->testOrThrow($current, $type, $value);
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
