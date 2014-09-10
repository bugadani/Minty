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
    //Environment options
    /**
     * @var Environment
     */
    private static $environment;

    /**
     * @var ExpressionTokenizer
     */
    private static $expressionTokenizer;
    private static $closingTags;
    private static $delimiters;
    private static $delimiterPatterns;
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
        if (self::$environment === $environment) {
            return;
        }

        self::$environment         = $environment;
        self::$fallbackTagName     = $environment->getOption('fallback_tag');
        self::$expressionTokenizer = $environment->getExpressionTokenizer();
        self::$delimiters          = $environment->getOption('delimiters');

        $blockEndPrefix = $environment->getOption('block_end_prefix');

        $blockTags = new \CallbackFilterIterator(
            new \ArrayIterator($environment->getTags()),
            function (Tag $tag) {
                return $tag->hasEndingTag();
            }
        );

        self::$closingTags = [];
        foreach ($blockTags as $name => $tag) {
            self::$closingTags[$blockEndPrefix . $name] = 'end' . $name;
        }

        self::$delimiterPatterns         = $this->getDelimiterPatterns();
        self::$tagEndSearchPattern       = $this->getTagEndMatchingPattern();
        self::$rawBlockClosingTagPattern = $this->getRawBlockClosingTagPattern($blockEndPrefix);
        self::$tokenSplitPattern         = $this->getTokenSplitPattern();
    }

    private function getDelimiterPatterns()
    {
        $delimiterPatterns = [];
        foreach (self::$delimiters as $name => $delimiters) {
            list($start, $end) = $delimiters;
            $delimiterPatterns[$name] = [
                preg_quote($start, '/'),
                preg_quote($end, '/')
            ];
        }

        if (self::$environment->getOption('tag_consumes_newline')) {
            $delimiterPatterns['tag'][1] .= '\n?';
        }

        return $delimiterPatterns;
    }

    private function getTagEndMatchingPattern()
    {
        $string    = "'(?:\\\\.|[^'\\\\])*'";
        $dq_string = '"(?:\\\\.|[^"\\\\])*"';

        $tagEnd = self::$delimiterPatterns['tag'][1];
        $wctEnd = self::$delimiterPatterns['whitespace_control_tag'][1];

        return "/^\\s*((?:{$string}|{$dq_string}|[^\"']+?)+?)\\s*({$wctEnd}\\s*|{$tagEnd})/i";
    }

    private function getRawBlockClosingTagPattern($blockEndPrefix)
    {
        list($tagStart, $tagEnd) = self::$delimiterPatterns['tag'];
        list($wctStart, $wctEnd) = self::$delimiterPatterns['whitespace_control_tag'];

        $endRaw = preg_quote($blockEndPrefix, '/') . 'raw';

        return "/((?:\\s*{$wctStart}|{$tagStart})\\s*{$endRaw}\\s*({$wctEnd}\\s*|{$tagEnd}))/i";
    }

    private function getTokenSplitPattern()
    {
        $patternParts = [];

        list($start, $end) = self::$delimiterPatterns['tag'];
        $patternParts[$start] = strlen($start);
        $patternParts[$end]   = strlen($end);

        list($start, $end) = self::$delimiterPatterns['comment'];
        $patternParts[$start] = strlen($start);
        $patternParts[$end]   = strlen($end);

        list($start, $end) = self::$delimiterPatterns['whitespace_control_tag'];
        $start = '\s*' . $start;
        $end   = $end . '\s*';

        $patternParts[$start] = strlen($start);
        $patternParts[$end]   = strlen($end);

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
            }
            $this->pushToken(Token::EOF);
        } else {
            list($delimiter, $offset) = $this->positions[$this->cursor];
            if ($this->lastOffset < $offset) {
                $this->pushTextToken($offset - $this->lastOffset);
                $this->lastOffset = $offset;
            }
            $this->lastOffset += strlen($delimiter);
            switch (trim($delimiter)) {
                case self::$delimiters['comment'][0]:
                    $this->tokenizeComment();
                    break;

                case self::$delimiters['tag'][0]:
                case self::$delimiters['whitespace_control_tag'][0]:
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
            $this->lastOffset += strlen($this->positions[$this->cursor][0]);
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
        self::$expressionTokenizer->setLine($this->line);
        $this->tokenBuffer = array_merge(
            $this->tokenBuffer,
            self::$expressionTokenizer->tokenize($expression)
        );
        $this->line += substr_count($expression, "\n");
        $this->pushToken(Token::TAG_END, $tagName);
    }

    private function tokenizeComment()
    {
        $commentEndDelimiter = self::$delimiters['comment'][1];
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
