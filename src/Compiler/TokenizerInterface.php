<?php

namespace Minty\Compiler;

interface TokenizerInterface
{
    /**
     * @param $string
     *
     * @return Stream
     */
    public function tokenize($string);

    /**
     * @return Token
     */
    public function nextToken();
}
