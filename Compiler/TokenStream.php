<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler;

class TokenStream
{
    /**
     * @var Token[]
     */
    private $tokens;
    private $consume_next_ws;
    private $expectations;
    private $expectation_count;

    public function __construct()
    {
        $this->tokens            = array();
        $this->consume_next_ws   = false;
        $this->expectations      = array();
        $this->expectation_count = 0;
    }

    /**
     * @return Token[]
     */
    public function getTokens()
    {
        return $this->tokens;
    }

    /**
     * @param Token $token
     */
    public function push(Token $token)
    {
        $token = $this->mergeTexts($token);
        if (!$token) {
            return;
        }
        $this->checkExpectations($token);

        $this->tokens[] = $token;

        $this->expectations[] = array();
        $this->expectation_count++;
    }

    /**
     * @return Token
     */
    public function pop($pop_expectations = true)
    {
        if ($pop_expectations) {
            array_pop($this->expectations);
        }
        return array_pop($this->tokens);
    }

    /**
     * @return Token|bool
     */
    public function nextTokenIf($type, $value = null)
    {
        $token = reset($this->tokens);
        if (!$token) {
            return false;
        }
        if ($token->test($type, $value)) {
            return $this->nextToken();
        }
        return false;
    }

    /**
     * @return Token|bool
     */
    public function nextTokenIfNot($type, $value = null)
    {
        $token = reset($this->tokens);
        if (!$token) {
            return false;
        }
        if (!$token->test($type, $value)) {
            return $this->nextToken();
        }
        return false;
    }

    /**
     * @return Token
     */
    public function nextToken()
    {
        return array_shift($this->tokens);
    }

    public function consumeNextWhitespace()
    {
        $this->consume_next_ws = true;
    }

    private function mergeTexts(Token $token)
    {
        if (!$token->test(Token::TEXT)) {
            return $token;
        }

        $value = $token->getValue();
        if ($value == '') {
            return false;
        }
        if (trim($value) == '' && $this->consume_next_ws) {
            $this->consume_next_ws = false;
            return false;
        }

        $last_token = $this->pop(false);
        if ($last_token === null) {
            return $token;
        }
        if ($last_token->test(Token::TEXT)) {
            $value = $last_token->getValue() . $value;
            $token = new Token(Token::TEXT, $value, $token->getLine());
        } else {
            $this->tokens[] = $last_token;
        }
        return $token;
    }

    /**
     * @param int $type
     * @param mixed $value
     * @return bool
     */
    public function test($type, $value = null)
    {
        return end($this->tokens)->test($type, $value);
    }

    public function hasExpectations()
    {
        foreach ($this->expectations as $expectation) {
            if (!empty($expectation)) {
                return true;
            }
        }
        return false;
    }

    public function expect($type, $value = null, $check_in = 1, $negative = false)
    {
        $this->expectations[$this->expectation_count][] = array(
            array(
                'type'     => $type,
                'value'    => $value,
                'in'       => $check_in,
                'negative' => $negative
            )
        );
        return $this;
    }

    public function also($type, $value = null, $check_in = 1, $negative = false)
    {
        $last_row   = array_pop($this->expectations[$this->expectation_count]);
        $last_row[] = array(
            'type'     => $type,
            'value'    => $value,
            'in'       => $check_in,
            'negative' => $negative,
        );

        $this->expectations[$this->expectation_count][] = $last_row;
        return $this;
    }

    public function then($type, $value = null, $check_in = 1, $negative = false)
    {
        $last_row         = array_pop($this->expectations[$this->expectation_count]);
        $last_expectation = end($last_row);
        $last_row[]       = array(
            'type'     => $type,
            'value'    => $value,
            'in'       => $last_expectation['in'] + $check_in,
            'negative' => $negative,
        );

        $this->expectations[$this->expectation_count][] = $last_row;
        return $this;
    }

    private function filterExpectations($filter)
    {
        foreach ($this->expectations as $row_group_key => &$rows) {
            foreach ($rows as $row_key => &$row) {
                foreach ($row as $key => &$expectation) {
                    if ($filter($expectation)) {
                        unset($row[$key]);
                    }
                }
                if (empty($row)) {
                    unset($rows[$row_key]);
                }
            }
            if (empty($rows)) {
                unset($this->expectations[$row_group_key]);
            }
        }
    }

    private function checkExpectations($token)
    {
        //decrease the check_in values
        $this->filterExpectations(function (&$row) {
            $row['in'] --;
            return $row['in'] < 0;
        });

        $filter = function ($expectation_row) use ($token) {
            $row_valid = true;
            foreach ($expectation_row as $expectation) {
                if ($expectation['in'] != 0) {
                    continue;
                }

                $match = $token->test($expectation['type'], $expectation['value']);
                $row_valid &= ($match xor $expectation['negative']);
            }
            return $row_valid;
        };

        foreach ($this->expectations as &$rows) {
            $rows = array_filter($rows, $filter);
            if (empty($rows)) {
                $message   = 'Unexpected %s token found in line %d.';
                $exception = sprintf($message, $token->getTypeString(), $token->getLine());
                throw new SyntaxException($exception);
            }
        }
        $this->filterExpectations(function ($row) {
            return $row['in'] <= 0;
        });
    }
}
