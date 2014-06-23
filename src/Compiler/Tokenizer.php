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
        $this->fallbackTagName = $environment->getOption('fallback_tag');
        $this->blockEndPrefix  = $environment->getOption('block_end_prefix');
        $this->operators       = $environment->getOperatorSymbols();
        $this->delimiters      = $environment->getOption('delimiters');

        $this->tags = $environment->getTags();
        foreach ($this->tags as $name => $tag) {
            if ($tag->hasEndingTag()) {
                $this->tags[$this->blockEndPrefix . $name] = 'end' . $name;
            }
        }

        $this->tokenSplitPattern      = $this->getTokenSplitPattern();
        $this->expressionPartsPattern = $this->getExpressionPartsPattern();
    }

    private function getExpressionPartsPattern()
    {
        $signs    = ' ';
        $patterns = array(
            '\$[a-zA-Z_\-]+[a-zA-Z_\-0-9]*' => 29,
            ':[a-zA-Z_\-0-9]+'              => 16,
            '(?<!\w)\d+(?:\.\d+)?'          => 20,
            '"(?:\\\\.|[^"\\\\])*"'         => 21,
            "'(?:\\\\.|[^'\\\\])*'"         => 21
        );

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
        arsort($patterns);
        $patterns = implode('|', array_keys($patterns));

        $signs = preg_quote($signs, '/');

        return "/({$patterns}|[{$signs}])/i";
    }

    private function getTokenSplitPattern()
    {
        $patternParts  = array();
        $delimiterList = array(
            $this->delimiters['comment'][0],
            $this->delimiters['comment'][1],
            $this->delimiters['tag'][0],
            $this->delimiters['tag'][1]
        );
        foreach ($delimiterList as $delimiter) {
            $delimiterPattern                = preg_quote($delimiter, '/');
            $patternParts[$delimiterPattern] = strlen($delimiterPattern);
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
        $commentEndDelimiter = $this->delimiters['comment'][1];
        while (isset($this->parts[++$this->position])) {
            if ($this->parts[$this->position] === $commentEndDelimiter) {
                return;
            }
            $this->line += substr_count($this->parts[$this->position], "\n");
        }
        throw new SyntaxException('Unterminated comment', $this->line);
    }

    private function tokenizeTag()
    {
        $tagExpression   = '';
        $tagEndDelimiter = $this->delimiters['tag'][1];

        while (isset($this->parts[++$this->position])) {
            switch ($this->parts[$this->position]) {
                case $tagEndDelimiter:
                    $tag = trim($tagExpression);
                    if ($tag === 'raw') {
                        $this->tokenizeRawBlock();
                    } else {
                        $this->processTag($tag);
                    }

                    return;

                case '"':
                case "'":
                    $tagExpression .= $this->tokenizeString($this->parts[$this->position]);
                    break;

                default:
                    $tagExpression .= $this->parts[$this->position];
                    break;
            }
        }
        throw new SyntaxException('Unterminated tag', $this->line);
    }

    private function tokenizeString($delimiter)
    {
        $string = $delimiter;
        while (isset($this->parts[++$this->position])) {
            $string .= $this->parts[$this->position];
            if ($this->parts[$this->position] !== $delimiter) {
                continue;
            }
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
        throw new SyntaxException('Unterminated string', $this->line);
    }

    private function processTag($tag)
    {
        //Try to find the tag name
        preg_match('/([^\s]*)(\s.*|)$/ADs', $tag, $parts);
        list(, $tagName, $expression) = $parts;

        //If the tag name is unknown, try to use the fallback
        if (!isset($this->tags[$tagName])) {
            $tagName    = $this->fallbackTagName;
            $expression = $tag;
        }

        if (is_string($this->tags[$tagName])) {
            $this->pushToken(Token::TAG, $this->tags[$tagName]);
        } else {
            $this->pushToken(Token::TAG, $tagName);
        }
        //tokenize the tag expression if any
        if ($expression === '') {
            return;
        }
        $this->pushToken(Token::EXPRESSION_START);

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
        if (in_array($part, $this->punctuation)) {
            $this->pushToken(Token::PUNCTUATION, $part);
        } elseif (in_array($part, $this->operators)) {
            $this->pushToken(Token::OPERATOR, $part);
        } elseif (is_numeric($part)) {
            $number = (float) $part;
            //check whether the number can be represented as an integer
            if (ctype_digit($part) && $number <= PHP_INT_MAX) {
                $number = (int) $part;
            }
            $this->pushToken(Token::LITERAL, $number);
        } else {
            switch ($part[0]) {
                case '"':
                case "'":
                    //strip backslashes from double-slashes and escaped string delimiters
                    $part = strtr($part, array('\\' . $part[0] => $part[0], '\\\\' => '\\'));
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
