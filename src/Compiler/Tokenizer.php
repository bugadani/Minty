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
    private static $tokenSplitPattern;
    private static $tagEndSearchPattern;
    private static $rawBlockClosingTagPattern;
    private static $fallbackTagName;

    // State properties
    private $line;
    private $tokenBuffer;
    private $positions;
    private $lastOffset;
    private $template;
    private $length;

    public function __construct(Environment $environment, ExpressionTokenizer $expressionTokenizer)
    {
        self::$expressionTokenizer = $expressionTokenizer;
        if (self::$environment === $environment) {
            return;
        }

        self::$environment     = $environment;
        self::$fallbackTagName = $environment->getOption('fallback_tag');
        self::$delimiters      = $environment->getOption('delimiters');
        self::$closingTags     = [];

        $blockEndPrefix = $environment->getOption('block_end_prefix');

        $blockTags = new \CallbackFilterIterator(
            new \ArrayIterator($environment->getTags()),
            function (Tag $tag) {
                return $tag->hasEndingTag();
            }
        );

        foreach ($blockTags as $name => $tag) {
            self::$closingTags[$blockEndPrefix . $name] = 'end' . $name;
        }

        $this->createPatterns($blockEndPrefix);
    }

    /**
     * @return array
     */
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

        $delimiterPatterns['whitespace_control_tag'][0] = '\s*' . $delimiterPatterns['whitespace_control_tag'][0];
        $delimiterPatterns['whitespace_control_tag'][1] = $delimiterPatterns['whitespace_control_tag'][1] . '\s*';

        return $delimiterPatterns;
    }

    private function createPatterns($blockEndPrefix)
    {
        $delimiterPatterns = $this->getDelimiterPatterns();

        $tokenPatternParts = [];
        foreach ($delimiterPatterns as $delimiters) {
            list($start, $end) = $delimiters;

            $tokenPatternParts[$start] = strlen($start);
            $tokenPatternParts[$end]   = strlen($end);
        }

        list($tagStart, $tagEnd) = $delimiterPatterns['tag'];
        list($wctStart, $wctEnd) = $delimiterPatterns['whitespace_control_tag'];

        arsort($tokenPatternParts);
        $tokenSplitPattern = implode('|', array_keys($tokenPatternParts));
        $blockEndPrefix    = preg_quote($blockEndPrefix, '/');

        self::$tagEndSearchPattern = "/^\\s*
        (
            (?:                           # this makes sure we don't match inside strings
                '(?:\\\\.|[^'\\\\])*'     # single quoted string
                |\"(?:\\\\.|[^\"\\\\])*\" # doubly quoted string
                |[^\"']+?                 # other characters
            )+?
        )
        \\s*({$wctEnd}\\s*|{$tagEnd})     # any tag ending delimiter
        /ix";

        self::$rawBlockClosingTagPattern = "/
        (
            (?:
                \\s*{$wctStart}|{$tagStart} # any tag opening delimiter
            )\\s*
            {$blockEndPrefix}raw            # endraw tagname
            \\s*({$wctEnd}\\s*|{$tagEnd})   # any tag ending delimiter
        )/ix";

        self::$tokenSplitPattern = "/({$tokenSplitPattern})/";
    }

    public function tokenize($template)
    {
        $this->line       = 1;
        $this->lastOffset = 0;

        $template       = str_replace(["\r\n", "\n\r", "\r"], "\n", $template);
        $this->template = $template;
        $this->length   = strlen($template);

        preg_match_all(self::$tokenSplitPattern, $template, $matches, PREG_OFFSET_CAPTURE);

        $this->positions = $matches[1];
        reset($this->positions);

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
        if (!($position = current($this->positions))) {
            if ($this->lastOffset < $this->length) {
                $this->pushTextToken($this->length - $this->lastOffset);
                $this->lastOffset = $this->length;
            }
            $this->pushToken(Token::EOF);
        } else {
            list($delimiter, $offset) = $position;
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
            next($this->positions);
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
            end($this->positions);
        } else {
            //search for the first element where offset is >= lastOffset
            while (current($this->positions)[1] < $this->lastOffset) {
                next($this->positions);
            }

            //increment lastOffset with the delimiters length
            $this->lastOffset += strlen(current($this->positions)[0]);
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
        while ($position = next($this->positions)) {
            list($delimiter, $offset) = $position;
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
