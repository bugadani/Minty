<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler;

use Minty\Compiler\Exceptions\SyntaxException;
use Minty\Environment;

class Tokenizer
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
    private static $closingTags;
    private static $operators;
    private static $delimiters;
    private static $delimiterLengths = [];
    private static $expressionPartsPattern;
    private static $tokenSplitPattern;
    private static $tagEndSearchPattern;
    private static $rawBlockClosingTagPattern;
    private static $fallbackTagName;

    // State properties
    private $line;
    private $tokenBuffer;
    private $positions;
    private $cursor;
    private $lastOffset;
    private $template;
    private $length;

    public function __construct(Environment $environment)
    {
        if (!self::$environment !== $environment) {
            self::$environment     = $environment;
            self::$fallbackTagName = $environment->getOption('fallback_tag');
            self::$delimiters      = $environment->getOption('delimiters');
            self::$operators       = $environment->getOperatorSymbols();

            $blockEndPrefix    = $environment->getOption('block_end_prefix');
            self::$closingTags = [];
            foreach ($environment->getTags() as $name => $tag) {
                if ($tag->hasEndingTag()) {
                    self::$closingTags[$blockEndPrefix . $name] = 'end' . $name;
                }
            }

            self::$tagEndSearchPattern       = $this->getTagEndMatchingPattern();
            self::$rawBlockClosingTagPattern = $this->getRawBlockClosingTagPattern($blockEndPrefix);
            self::$tokenSplitPattern         = $this->getTokenSplitPattern();
            self::$expressionPartsPattern    = $this->getExpressionPartsPattern();
        }
    }

    private function getTagEndMatchingPattern()
    {
        $string              = "'(?:\\\\.|[^'\\\\])*'";
        $dq_string           = '"(?:\\\\.|[^"\\\\])*"';
        $tagOpeningDelimiter = self::$delimiters['tag'][1];

        return "/^\\s*((?:{$string}|{$dq_string}|[^\"']+?)+?)\\s*({$tagOpeningDelimiter})/i";
    }

    private function getRawBlockClosingTagPattern($blockEndPrefix)
    {
        list($tagStartDelimiter, $tagEndDelimiter) = self::$delimiters['tag'];
        $endRaw = preg_quote($blockEndPrefix, '/') . 'raw';

        return "/({$tagStartDelimiter}\\s*{$endRaw}\\s*({$tagEndDelimiter}))/i";
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

        $symbols = array_merge(self::$operators, self::$punctuation, array_keys(self::$literals));
        foreach ($symbols as $symbol) {
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

    private function getTokenSplitPattern()
    {
        foreach (self::$delimiters as $delimiters) {
            foreach ((array)$delimiters as $delimiter) {
                $length = strlen($delimiter);

                $patternParts[preg_quote($delimiter, '/')] = $length;
                self::$delimiterLengths[$delimiter]        = $length;
            }
        }

        arsort($patternParts);
        $pattern = implode('|', array_keys($patternParts));

        return "/({$pattern})/";
    }

    public function tokenize($template)
    {
        $this->line       = 1;
        $this->cursor     = -1;
        $this->lastOffset = 0;

        $template       = str_replace(["\r\n", "\n\r", "\r"], "\n", $template);
        $this->template = $template;
        $this->length   = strlen($template);

        preg_match_all(self::$tokenSplitPattern, $template, $matches, PREG_OFFSET_CAPTURE);
        $this->positions = $matches[1];

        $this->tokenBuffer = [];

        return new Stream($this);
    }

    public function nextToken()
    {
        //Sometimes the next token is an empty text token that is skipped.
        //This loop ensures that we always have a valid token to return.
        while (empty($this->tokenBuffer)) {
            $this->getNextToken();
        }

        return array_shift($this->tokenBuffer);
    }

    private function getNextToken()
    {
        if (!isset($this->positions[++$this->cursor])) {
            if ($this->lastOffset < $this->length) {
                $this->pushTextToken($this->length - $this->lastOffset);
                $this->lastOffset = $this->length;
            } else {
                $this->pushToken(Token::EOF);
            }
        } else {
            list($delimiter, $offset) = $this->positions[$this->cursor];
            if ($this->lastOffset < $offset) {
                $this->pushTextToken($offset - $this->lastOffset);
                $this->lastOffset = $offset;
            }
            $this->lastOffset += self::$delimiterLengths[$delimiter];
            switch ($delimiter) {
                case self::$delimiters['comment'][0]:
                    $this->tokenizeComment();
                    break;

                case self::$delimiters['tag'][0]:
                    $this->tokenizeTag();
                    break;
            }
        }
    }

    private function tokenizeTag()
    {
        $template = substr($this->template, $this->lastOffset);
        if (!preg_match(self::$tagEndSearchPattern, $template, $match, PREG_OFFSET_CAPTURE)) {
            throw new SyntaxException('Unterminated tag', $this->line);
        }
        $this->seek($match[2][1]);

        $tag = $match[1][0];
        if ($tag === 'raw') {
            $this->tokenizeRawBlock();
        } else {
            $this->processTag($tag);
        }
    }

    private function tokenizeRawBlock()
    {
        $template = substr($this->template, $this->lastOffset);
        if (!preg_match(self::$rawBlockClosingTagPattern, $template, $match, PREG_OFFSET_CAPTURE)) {
            throw new SyntaxException('Unterminated raw block', $this->line);
        }
        $this->pushTextToken($match[1][1]);
        $this->seek($match[2][1]);
    }

    /**
     * @param $offset
     */
    private function seek($offset)
    {
        $this->lastOffset += $offset;

        if ($this->lastOffset >= $this->length) {
            //not much to do, set the cursor to the last element
            $this->cursor = count($this->positions) - 1;
        } else {
            //search for the first element where offset is >= lastOffset
            while ($this->positions[$this->cursor][1] < $this->lastOffset) {
                $this->cursor++;
            }

            //increment lastOffset with the delimiters length
            $this->lastOffset += self::$delimiterLengths[$this->positions[$this->cursor][0]];
        }
    }

    private function processTag($tag)
    {
        //Try to find the tag name
        preg_match('/(\S*)(?:\s*(.*|))$/ADs', $tag, $parts);
        list(, $tagName, $expression) = $parts;

        //If the tag name is unknown, try to use the fallback
        if (isset(self::$closingTags[$tagName])) {
            $tagName = self::$closingTags[$tagName];
        } elseif (!self::$environment->hasTag($tagName)) {
            $tagName    = self::$fallbackTagName;
            $expression = $tag;
        }

        $this->pushToken(Token::TAG_START, $tagName);
        $this->tokenizeTagExpression($expression);
        $this->pushToken(Token::TAG_END, $tagName);
    }

    /**
     * @param $expression
     */
    private function tokenizeTagExpression($expression)
    {
        if ($expression === '') {
            return;
        }

        $flags = PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY;
        $parts = preg_split(self::$expressionPartsPattern, $expression, 0, $flags);

        foreach ($parts as $part) {
            //We can safely skip spaces
            if ($part !== ' ') {
                if (trim($part) === '') {
                    //Whitespace strings only matter for line numbering
                    $this->line += substr_count($part, "\n");
                } else {
                    $this->tokenizeExpressionPart($part);
                }
            }
        }
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

    private function tokenizeComment()
    {
        $commentEndDelimiter = self::$delimiters['comment'][1];
        while (isset($this->positions[++$this->cursor])) {
            list($delimiter, $offset) = $this->positions[$this->cursor];
            if ($delimiter === $commentEndDelimiter) {
                $length = $offset - $this->lastOffset;

                $this->line += substr_count($this->template, "\n", $this->lastOffset, $length);
                $this->lastOffset = $offset + self::$delimiterLengths[$commentEndDelimiter];

                return;
            }
        }
        throw new SyntaxException('Unterminated comment', $this->line);
    }

    private function pushTextToken($length)
    {
        if ($length === 0) {
            return;
        }

        $text = substr($this->template, $this->lastOffset, $length);
        $this->pushToken(Token::TEXT, $text);
        $this->line += substr_count($text, "\n");
    }

    private function pushToken($type, $value = null)
    {
        $this->tokenBuffer[] = new Token($type, $value, $this->line);
    }
}
