<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler;

use Minty\Environment;

class ExpressionTokenizer implements TokenizerInterface
{
    //Constants
    private static $punctuation = [
        ','  => ',',
        '['  => '[',
        ']'  => ']',
        '('  => '(',
        ')'  => ')',
        ':'  => ':',
        '?'  => '?',
        '=>' => '=>'
    ];

    //Environment options
    /**
     * @var Environment
     */
    private static $environment;
    private static $expressionPartsPattern;
    private static $operators;

    private $line = 0;

    /**
     * @var \Iterator
     */
    private $iterator;

    public function __construct(Environment $environment)
    {
        if (self::$environment !== $environment) {
            self::$environment = $environment;
            self::$operators   = array_combine(
                $environment->getOperatorSymbols(),
                $environment->getOperatorSymbols()
            );

            self::$expressionPartsPattern = $this->getExpressionPartsPattern();
        }
    }

    private function getExpressionPartsPattern()
    {
        $signs    = ' ';
        $patterns = [
            '\$[a-zA-Z_]+[a-zA-Z_0-9]+' => 25, //$variable
            ':[a-zA-Z_\-0-9]+'          => 16, //:short-string
            '"(?:\\\\.|[^"\\\\])*"'     => 21, //double quoted string
            "'(?:\\\\.|[^'\\\\])*'"     => 21, //single quoted string
            '(?<!\w)\d+(?:\.\d+)?'      => 20 //number
        ];

        $iterator = new \AppendIterator();
        $iterator->append(
            new \CallbackFilterIterator(
                new \ArrayIterator(self::$operators),
                function ($operator) {
                    return !ctype_alpha($operator);
                }
            )
        );
        $iterator->append(new \ArrayIterator(self::$punctuation));

        foreach ($iterator as $symbol) {
            $length = strlen($symbol);
            if ($length === 1) {
                $signs .= $symbol;
            } else {
                if (strpos($symbol, ' ') !== false) {
                    $symbol = "(?<=^|\\W){$symbol}(?=[\\s()\\[\\]]|$)";
                } else {
                    $symbol = preg_quote($symbol, '/');
                }
                $patterns[$symbol] = $length;
            }
        }
        arsort($patterns);
        $patterns = implode('|', array_keys($patterns));

        $signs = preg_quote($signs, '/');

        return "/({$patterns}|[{$signs}])/i";
    }

    public function setLine($line)
    {
        $this->line = $line;
    }

    /**
     * @param $expression
     *
     * @return Token[]
     */
    public function tokenize($expression)
    {
        if ($expression !== '') {
            $flags          = PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY;
            $this->iterator = new \CallbackFilterIterator(
                new \ArrayIterator(
                    preg_split(self::$expressionPartsPattern, $expression, 0, $flags)
                ),
                function ($value) {
                    //skip spaces
                    if ($value === ' ') {
                        return false;
                    }

                    //skip whitespaces but count newlines
                    if (rtrim($value) === '') {
                        $this->line += substr_count($value, "\n");

                        return false;
                    }

                    return true;
                }
            );
            $this->iterator->rewind();
        } else {
            $this->iterator = new \EmptyIterator();
        }

        return new Stream($this);
    }

    public function nextToken()
    {
        if ($this->iterator->valid()) {
            $token = $this->tokenizeExpressionPart($this->iterator->current());
            $this->iterator->next();
        } else {
            $token = $this->token(Token::EOF);
            $this->line = 0;
        }

        return $token;
    }

    private function tokenizeExpressionPart($part)
    {
        if (isset(self::$punctuation[$part])) {
            $token = $this->token(Token::PUNCTUATION, $part);
        } elseif (isset(self::$operators[$part])) {
            $token = $this->token(Token::OPERATOR, $part);
        } elseif (is_numeric($part)) {
            $number = (float) $part;
            //check whether the number can be represented as an integer
            if (ctype_digit($part) && $number <= PHP_INT_MAX) {
                $number = (int) $part;
            }
            $token = $this->token(Token::LITERAL, $number);
        } else {
            switch ($part[0]) {
                case '"':
                case "'":
                    //strip backslashes from double-slashes and escaped string delimiters
                    $part  = strtr($part, ['\\' . $part[0] => $part[0], '\\\\' => '\\']);
                    $token = $this->token(Token::STRING, substr($part, 1, -1));
                    $this->line += substr_count($part, "\n");
                    break;

                case ':':
                    $token = $this->token(Token::STRING, substr($part, 1));
                    break;

                case '$':
                    $token = $this->token(Token::VARIABLE, substr($part, 1));
                    break;

                default:
                    switch (strtolower($part)) {
                        case 'null':
                            $token = $this->token(Token::LITERAL, null);
                            break;

                        case 'true':
                            $token = $this->token(Token::LITERAL, true);
                            break;

                        case 'false':
                            $token = $this->token(Token::LITERAL, false);
                            break;

                        default:
                            $token = $this->token(Token::IDENTIFIER, $part);
                            break;
                    }
                    break;
            }
        }

        return $token;
    }

    private function token($type, $value = null)
    {
        return new Token($type, $value, $this->line);
    }
}
