<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler;

use Minty\Environment;

class ExpressionTokenizer
{
    //Constants
    private static $literals = [
        'null'  => null,
        'true'  => true,
        'false' => false
    ];
    private static $punctuation = [',', '[', ']', '(', ')', ':', '?', '=>'];

    //Environment options
    /**
     * @var Environment
     */
    private static $environment;
    private static $expressionPartsPattern;
    private static $operators;

    private $line = 0;
    private $tokenBuffer;

    public function __construct(Environment $environment)
    {
        if (self::$environment === $environment) {
            return;
        }

        self::$environment = $environment;
        self::$operators   = $environment->getOperatorSymbols();

        self::$expressionPartsPattern = $this->getExpressionPartsPattern();
    }

    private function getExpressionPartsPattern()
    {
        $signs    = ' ';
        $patterns = [
            '(?:\$[a-zA-Z_]+|:)[a-zA-Z_\-0-9]+' => 33, //variable ($) or short-string (:)
            '"(?:\\\\.|[^"\\\\])*"'             => 21, //double quoted string
            "'(?:\\\\.|[^'\\\\])*'"             => 21, //single quoted string
            '(?<!\w)\d+(?:\.\d+)?'              => 20 //number
        ];

        $iterator = new \AppendIterator();
        $iterator->append(new \ArrayIterator(self::$operators));
        $iterator->append(new \ArrayIterator(self::$punctuation));
        $iterator->append(new \ArrayIterator(array_keys(self::$literals)));

        foreach ($iterator as $symbol) {
            $length = strlen($symbol);
            if ($length === 1) {
                $signs .= $symbol;
            } else {
                if (preg_match('/^[a-zA-Z\ ]+$/', $symbol)) {
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
        $this->tokenBuffer = [];

        if ($expression !== '') {
            $flags    = PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY;
            $iterator = new \CallbackFilterIterator(
                new \ArrayIterator(
                    preg_split(self::$expressionPartsPattern, $expression, 0, $flags)
                ),
                function ($value) {
                    return $value !== ' ';
                }
            );
            foreach ($iterator as $part) {
                if (trim($part) === '') {
                    //Whitespace strings only matter for line numbering
                    $this->line += substr_count($part, "\n");
                } else {
                    $this->tokenizeExpressionPart($part);
                }
            }
        }
        $this->line = 0;

        return $this->tokenBuffer;
    }

    private function tokenizeExpressionPart($part)
    {
        if (in_array($part, self::$punctuation)) {
            $this->pushToken(Token::PUNCTUATION, $part);
        } elseif (in_array($part, self::$operators)) {
            $this->pushToken(Token::OPERATOR, $part);
        } elseif (is_numeric($part)) {
            $number = (float)$part;
            //check whether the number can be represented as an integer
            if (ctype_digit($part) && $number <= PHP_INT_MAX) {
                $number = (int)$part;
            }
            $this->pushToken(Token::LITERAL, $number);
        } else {
            switch ($part[0]) {
                case '"':
                case "'":
                    //strip backslashes from double-slashes and escaped string delimiters
                    $part = strtr($part, ['\\' . $part[0] => $part[0], '\\\\' => '\\']);
                    $this->pushToken(Token::STRING, substr($part, 1, -1));
                    $this->line += substr_count($part, "\n");
                    break;

                case ':':
                    $this->pushToken(Token::STRING, substr($part, 1));
                    break;

                case '$':
                    $this->pushToken(Token::VARIABLE, substr($part, 1));
                    break;

                default:
                    $lowerCasePart = strtolower($part);
                    if (array_key_exists($lowerCasePart, self::$literals)) {
                        $this->pushToken(Token::LITERAL, self::$literals[$lowerCasePart]);
                    } else {
                        $this->pushToken(Token::IDENTIFIER, $part);
                    }
                    break;
            }
        }
    }

    private function pushToken($type, $value = null)
    {
        $this->tokenBuffer[] = new Token($type, $value, $this->line);
    }
}
