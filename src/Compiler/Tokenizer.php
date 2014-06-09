<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler;

use Modules\Templating\Compiler\Exceptions\ParseException;
use Modules\Templating\Compiler\Exceptions\SyntaxException;
use Modules\Templating\Environment;

class Tokenizer
{
    /**
     * @var Tag[]
     */
    private $tags;
    private $operators;
    private $delimiters;
    private $expressionPartsPattern;
    private $tokenSplitPattern;
    private $fallbackTagName;
    private $blockEndPrefix;
    private $blockEndingTags = array();
    private $punctuation = array(',', '[', ']', '(', ')', ':', '?', '=>');
    private $literals = array(
        'null'  => null,
        'true'  => true,
        'false' => false
    );

    // State properties
    /**
     * @var Stream
     */
    private $tokens;
    private $line;
    private $parts;
    private $position;

    public function __construct(Environment $environment)
    {
        $this->fallbackTagName = $environment->getOption('fallback_tag', false);
        $this->blockEndPrefix  = $environment->getOption('block_end_prefix', 'end');
        $this->operators       = $environment->getOperatorSymbols();
        $this->delimiters      = $environment->getOption(
            'delimiters',
            array(
                'tag'     => array('{', '}'),
                'comment' => array('{#', '#}')
            )
        );

        $this->tags = $environment->getTags();
        foreach ($this->tags as $name => $tag) {
            if ($tag->hasEndingTag()) {
                $this->blockEndingTags[$this->blockEndPrefix . $name] = 'end' . $name;
            }
        }

        $this->tokenSplitPattern      = $this->getTokenSplitPattern();
        $this->expressionPartsPattern = $this->getExpressionPartsPattern(
            array(
                '$[a-zA-Z]+[a-zA-Z_\-0-9]*',
                ':[a-zA-Z]+[a-zA-Z_\-0-9]*',
                '(?<!\w)\d+(?:\.\d+)?',
                '"(?:\\\\.|[^"\\\\])*"',
                "'(?:\\\\.|[^'\\\\])*'"
            )
        );
    }

    private function getExpressionPartsPattern(array $dataPatterns)
    {
        $patterns = array();
        $signs    = ' ';

        $symbols = array_merge($this->operators, $this->punctuation, array_keys($this->literals));
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
        foreach ($dataPatterns as $pattern) {
            $patterns[$pattern] = strlen($pattern);
        }
        arsort($patterns);

        $patterns = implode('|', array_keys($patterns));
        $signs    = preg_quote($signs, '/');

        return "/({$patterns}|[{$signs}])/i";
    }

    private function getTokenSplitPattern()
    {
        $patternParts = array();
        foreach ($this->delimiters as $delimiters) {
            foreach ($delimiters as $delimiter) {
                $pattern                = preg_quote($delimiter, '/');
                $patternParts[$pattern] = strlen($pattern);
            }
        }
        arsort($patternParts);
        $pattern = implode('|', array_keys($patternParts));

        return "/({$pattern}|[\"'])/";
    }

    public function tokenize($template)
    {
        $this->line     = 1;
        $this->tokens   = new Stream();
        $this->position = -1;

        $template = str_replace(array("\r\n", "\n\r", "\r"), "\n", $template);

        $flags       = PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY;
        $this->parts = preg_split($this->tokenSplitPattern, $template, 0, $flags);

        $currentText = '';
        while (isset($this->parts[++$this->position])) {
            switch ($this->parts[$this->position]) {
                case $this->delimiters['comment'][0]:
                    $this->pushTextToken($currentText);
                    $currentText = '';
                    $this->tokenizeComment();
                    break;

                case $this->delimiters['tag'][0]:
                    $this->pushTextToken($currentText);
                    $currentText = '';
                    $this->tokenizeTag();
                    break;

                default:
                    $currentText .= $this->parts[$this->position];
                    break;
            }
        }

        $this->pushTextToken($currentText);
        $this->pushToken(Token::EOF);

        $this->tokens->rewind();

        return $this->tokens;
    }

    private function tokenizeComment()
    {
        while (isset($this->parts[++$this->position])) {
            if ($this->parts[$this->position] === $this->delimiters['comment'][1]) {
                return;
            }
            $this->line += substr_count($this->parts[$this->position], "\n");
        }
        throw new SyntaxException('Unterminated comment', $this->line);
    }

    private function tokenizeTag()
    {
        $tagExpression = '';
        while (isset($this->parts[++$this->position])) {
            $part = $this->parts[$this->position];

            switch ($part) {
                case $this->delimiters['tag'][1]:
                    $tag = trim($tagExpression);
                    if ($tag === 'raw') {
                        $this->tokenizeRawBlock();
                    } elseif (isset($this->blockEndingTags[$tag])) {
                        //Since ending tags can't have arguments, they are handled first
                        $this->pushToken(Token::TAG, $this->blockEndingTags[$tag]);
                    } else {
                        $this->processTag($tag);
                    }

                    return;

                case '"':
                case "'":
                    $tagExpression .= $this->tokenizeString($part);
                    break;

                default:
                    $tagExpression .= $part;
                    break;
            }

        }
        throw new SyntaxException('Unterminated tag', $this->line);
    }

    private function tokenizeString($delimiter)
    {
        $string = $delimiter;
        while (isset($this->parts[++$this->position])) {
            $part = $this->parts[$this->position];

            $string .= $part;
            if ($part === $delimiter) {
                $inString = false;
                //Let's walk from the previous character backwards
                $offset = strlen($string) - 1;
                while ($offset > 0 && $string[--$offset] === '\\') {
                    //If we find one backslash, we flip back the flag to true
                    //2 backslashes, flag is false... even = the string has ended
                    $inString = !$inString;
                }

                if (!$inString) {
                    return $string;
                }
            }
        }
        throw new SyntaxException('Unterminated string', $this->line);
    }

    private function processTag($tag)
    {
        //Try to find the tag name
        preg_match('/(.*?)(\s.*|)$/ADs', $tag, $parts);
        $tagName = $parts[1];

        //If the tag name is unknown, try to use the fallback
        if (!isset($this->tags[$tagName])) {
            $tagName    = $this->fallbackTagName;
            $expression = $tag;

            if (!isset($this->tags[$tagName])) {
                throw new ParseException("Unknown tag \"{$tagName}\"", $this->line);
            }
        } else {
            $expression = $parts[2];
        }
        $this->pushToken(Token::TAG, $tagName);

        //tokenize the tag expression if any
        if ($expression !== '') {
            $this->pushToken(Token::EXPRESSION_START, $tagName);

            $flags = PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY;
            $parts = preg_split($this->expressionPartsPattern, $expression, 0, $flags);

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
            $this->pushToken(Token::EXPRESSION_END);
        }
    }

    private function tokenizeRawBlock()
    {
        $text                = '';
        $endRaw              = $this->blockEndPrefix . 'raw';
        $tagOpeningDelimiter = $this->delimiters['tag'][0];
        $tagClosingDelimiter = $this->delimiters['tag'][1];

        while (isset($this->parts[++$this->position])) {
            $pos = $this->position;

            //Check if the current position is a tag opening delimiter
            if ($this->parts[$pos] === $tagOpeningDelimiter) {

                //Check if the tag is a raw block closing tag
                if (isset($this->parts[++$pos]) && trim($this->parts[$pos]) === $endRaw) {

                    //Check if the tag is closed
                    if (isset($this->parts[++$pos]) && $this->parts[$pos] === $tagClosingDelimiter) {
                        $this->position = $pos;
                        $this->pushTextToken($text);

                        return;
                    }
                }
            }
            $text .= $this->parts[$this->position];
        }
        throw new SyntaxException('Unterminated raw block', $this->line);
    }

    private function tokenizeExpressionPart($part)
    {
        static $escapedCharacters = array(
            "'" => array(
                "\\'"  => "'",
                '\\\\' => '\\'
            ),
            '"' => array(
                '\\"'  => '"',
                '\\\\' => '\\'
            )
        );

        if (in_array($part, $this->punctuation)) {
            $this->pushToken(Token::PUNCTUATION, $part);
        } elseif (in_array($part, $this->operators)) {
            $this->pushToken(Token::OPERATOR, $part);
        } elseif (is_numeric($part)) {
            $this->pushToken(Token::LITERAL, $part);
        } else {
            switch ($part[0]) {
                case '"':
                case "'":
                    //strip backslashes from double-slashes and escaped string delimiters
                    $part = strtr($part, $escapedCharacters[$part[0]]);
                    $this->pushToken(Token::STRING, substr($part, 1, -1));
                    $this->line += substr_count($part, "\n");
                    break;

                case ':':
                    $this->pushToken(Token::STRING, ltrim($part, ':'));
                    break;

                case '$':
                    $this->pushToken(Token::VARIABLE, ltrim($part, '$'));
                    break;

                default:
                    $lowerCasePart = strtolower($part);
                    if (array_key_exists($lowerCasePart, $this->literals)) {
                        $this->pushToken(Token::LITERAL, $this->literals[$lowerCasePart]);
                    } else {
                        $this->pushToken(Token::IDENTIFIER, $part);
                    }
                    break;
            }
        }
    }

    public function pushToken($type, $value = null)
    {
        $this->tokens->push(new Token($type, $value, $this->line));
    }

    public function pushTextToken($value)
    {
        if ($value !== '') {
            $this->pushToken(Token::TEXT, $value);
            $this->line += substr_count($value, "\n");
        }
    }
}
