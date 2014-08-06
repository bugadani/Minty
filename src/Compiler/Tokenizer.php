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
     * @var Environment
     */
    private $environment;

    /**
     * @var string[]
     */
    private $closingTags = array();
    private $operators;
    private $delimiters;
    private $expressionPartsPattern;
    private $tokenSplitPattern;
    private $tagEndSearchPattern;
    private $rawBlockClosingTagPattern;
    private $fallbackTagName;
    private $punctuation = array(',', '[', ']', '(', ')', ':', '?', '=>');
    private $literals = array(
        'null'  => null,
        'true'  => true,
        'false' => false
    );

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
        $this->fallbackTagName = $environment->getOption('fallback_tag');
        $this->delimiters      = $environment->getOption('delimiters');
        $this->operators       = $environment->getOperatorSymbols();
        $this->environment     = $environment;

        $blockEndPrefix = $environment->getOption('block_end_prefix');
        foreach ($environment->getTags() as $name => $tag) {
            if ($tag->hasEndingTag()) {
                $this->closingTags[$blockEndPrefix . $name] = 'end' . $name;
            }
        }

        $this->tagEndSearchPattern       = $this->getTagEndMatchingPattern();
        $this->rawBlockClosingTagPattern = $this->getRawBlockClosingTagPattern($blockEndPrefix);
        $this->tokenSplitPattern         = $this->getTokenSplitPattern();
        $this->expressionPartsPattern    = $this->getExpressionPartsPattern();
    }

    private function getTagEndMatchingPattern()
    {
        $string      = "'(?:\\\\.|[^'\\\\])*'";
        $dq_string   = '"(?:\\\\.|[^"\\\\])*"';

        return "/^\\s*((?:{$string}|{$dq_string}|[^\"']+?)+?)\\s*({$this->delimiters['tag'][1]})/i";
    }

    private function getRawBlockClosingTagPattern($blockEndPrefix)
    {
        list($tagStartDelimiter, $tagEndDelimiter) = $this->delimiters['tag'];
        $endRaw = preg_quote($blockEndPrefix, '/') . 'raw';

        return "/({$tagStartDelimiter}\\s*{$endRaw}\\s*({$tagEndDelimiter}))/i";
    }

    private function getExpressionPartsPattern()
    {
        $signs    = ' ';
        $patterns = array(
            '(?:\$[a-zA-Z_]+|:)[a-zA-Z_\-0-9]+' => 33, //variable ($) or short-string (:)
            '"(?:\\\\.|[^"\\\\])*"'             => 21, //double quoted string
            "'(?:\\\\.|[^'\\\\])*'"             => 21, //single quoted string
            '(?<!\w)\d+(?:\.\d+)?'              => 20 //number
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
        foreach ($this->delimiters as $delimiters) {
            foreach ((array)$delimiters as $delimiter) {
                $patternParts[preg_quote($delimiter, '/')] = strlen($delimiter);
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

        $template       = str_replace(array("\r\n", "\n\r", "\r"), "\n", $template);
        $this->template = $template;
        $this->length   = strlen($template);

        preg_match_all($this->tokenSplitPattern, $template, $matches, PREG_OFFSET_CAPTURE);
        $this->positions = $matches[1];

        $this->tokenBuffer = array();

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
            if ($this->lastOffset !== $this->length) {
                $this->pushTextToken($this->length - $this->lastOffset);
            } else {
                $this->pushToken(Token::EOF);
            }
        } else {
            list($delimiter, $offset) = $this->positions[$this->cursor];
            if ($this->lastOffset !== $offset) {
                $this->pushTextToken($offset - $this->lastOffset);
            }
            $this->lastOffset += strlen($delimiter);
            switch ($delimiter) {
                case $this->delimiters['comment'][0]:
                    $this->tokenizeComment();
                    break;

                case $this->delimiters['tag'][0]:
                    $this->tokenizeTag();
                    break;
            }
        }
    }

    private function tokenizeTag()
    {
        $template = substr($this->template, $this->lastOffset);
        if (!preg_match($this->tagEndSearchPattern, $template, $match, PREG_OFFSET_CAPTURE)) {
            throw new SyntaxException('Unterminated tag', $this->line);
        }
        $this->seek($this->lastOffset + $match[2][1]);
        $this->lastOffset += $match[2][1] + strlen($match[2][0]);

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
        if (!preg_match($this->rawBlockClosingTagPattern, $template, $match, PREG_OFFSET_CAPTURE)) {
            throw new SyntaxException('Unterminated raw block', $this->line);
        }
        $this->pushTextToken($match[1][1]);
        $this->lastOffset += strlen($match[1][0]);
        $this->seek($match[2][1] + $match[1][1]);
    }

    /**
     * @param $offset
     */
    private function seek($offset)
    {
        while (isset($this->positions[$this->cursor]) && $this->positions[$this->cursor][1] < $offset) {
            $this->cursor++;
        }
    }

    private function processTag($tag)
    {
        //Try to find the tag name
        preg_match('/(\S*)(\s.*|)$/ADs', $tag, $parts);
        list(, $tagName, $expression) = $parts;

        //If the tag name is unknown, try to use the fallback
        if (isset($this->closingTags[$tagName])) {
            $tagName = $this->closingTags[$tagName];
        } elseif (!$this->environment->hasTag($tagName)) {
            $tagName    = $this->fallbackTagName;
            $expression = $tag;
        }

        $this->pushToken(Token::TAG_START, $tagName);

        //tokenize the tag expression if any
        if ($expression !== '') {
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
        }
        $this->pushToken(Token::TAG_END, $tagName);
    }

    private function tokenizeExpressionPart($part)
    {
        if (in_array($part, $this->punctuation)) {
            $this->pushToken(Token::PUNCTUATION, $part);
        } elseif (in_array($part, $this->operators)) {
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

    private function tokenizeComment()
    {
        $commentEndDelimiter = $this->delimiters['comment'][1];
        while (isset($this->positions[++$this->cursor])) {
            list($delimiter, $offset) = $this->positions[$this->cursor];
            if ($delimiter === $commentEndDelimiter) {
                $length = $offset - $this->lastOffset;

                $this->line += substr_count($this->template, "\n", $this->lastOffset, $length);
                $this->lastOffset = $offset + strlen($commentEndDelimiter);

                return;
            }
        }
        throw new SyntaxException('Unterminated comment', $this->line);
    }

    private function pushTextToken($length)
    {
        if ($length > 0) {
            $text = substr($this->template, $this->lastOffset, $length);
            $this->pushToken(Token::TEXT, $text);
            $this->line += substr_count($text, "\n");
            $this->lastOffset += $length;
        }
    }

    private function pushToken($type, $value = null)
    {
        $this->tokenBuffer[] = new Token($type, $value, $this->line);
    }
}
