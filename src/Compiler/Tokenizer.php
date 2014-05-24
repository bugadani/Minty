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
     * @var Stream
     */
    private $tokens;
    private $operators = array();
    private $delimiters;

    /**
     * @var Tag[]
     */
    private $tags = array();

    /**
     * @var Tag[]
     */
    private $patternBasedTags = array();

    private $patterns;
    private $inRaw;
    private $punctuation;
    private $line;
    private $fallbackTagName;
    private $blockEndPrefix;
    private $blockEndingTags;

    public function __construct(Environment $environment)
    {
        $this->punctuation     = array(',', '[', ']', '(', ')', ':', '?', '=>');
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

        $this->blockEndingTags = array(
            $this->blockEndPrefix . 'raw' => 'endraw'
        );
        foreach ($environment->getTags() as $name => $tag) {
            if ($tag->hasEndingTag()) {
                $this->blockEndingTags[$this->blockEndPrefix . $name] = 'end' . $name;
            }

            if ($tag->isPatternBased()) {
                $this->patternBasedTags[$name] = $tag;
            } else {
                $this->tags[$name] = $tag;
            }
        }

        $literals = array(
            'true',
            'false',
            'null',
            ':[a-zA-Z]+[a-zA-Z_\-0-9]*',
            '(?<!\w)\d+(?:\.\d+)?',
            '"(?:\\\\.|[^"\\\\])*"',
            "'(?:\\\\.|[^'\\\\])*'"
        );

        $literal_pattern = implode('|', $literals);
        $this->patterns  = array(
            'operator' => $this->getOperatorPattern(),
            'literal'  => "/({$literal_pattern})/i"
        );
    }

    private function getOperatorPattern()
    {
        $operators = array();
        $signs     = ' ';

        $symbols = array_merge($this->operators, $this->punctuation);
        foreach ($symbols as $symbol) {
            $length = strlen($symbol);
            if ($length == 1) {
                $signs .= $symbol;
            } else {
                $quotedSymbol = preg_quote($symbol, '/');
                if (preg_match('/^[a-zA-Z\ ]+$/', $symbol)) {
                    $quotedSymbol = "(?<=^|\\W){$quotedSymbol}(?=[\\s()\\[\\]]|$)";
                }
                $operators[$quotedSymbol] = $length;
            }
        }
        arsort($operators);

        $operators = implode('|', array_keys($operators));
        $signs     = preg_quote($signs, '/');

        return "/({$operators}|[{$signs}])/i";
    }

    public function tokenize($template)
    {
        $this->line   = 1;
        $this->tokens = new Stream();
        $this->inRaw  = false;

        $in_comment                  = false;
        $in_tag                      = false;
        $in_string                   = false;
        $tagStartPosition            = 0;
        $tagOpeningDelimiterPosition = 0;

        $cursor        = 0;
        $pattern_parts = array();
        foreach ($this->delimiters as $delimiter) {
            $opening                 = preg_quote($delimiter[0], '/');
            $closing                 = preg_quote($delimiter[1], '/');
            $pattern                 = $opening . '|' . $closing;
            $pattern_parts[$pattern] = strlen($pattern);
        }
        arsort($pattern_parts);
        $delimiterPatterns = implode('|', array_keys($pattern_parts));

        $endraw                    = $this->blockEndPrefix . 'raw';
        $tagOpeningDelimiterLength = strlen($this->delimiters['tag'][0]);
        $tagClosingDelimiterLength = strlen($this->delimiters['tag'][1]);

        $flags = PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE;
        $parts = preg_split("/({$delimiterPatterns}|[\"'])/", $template, -1, $flags);
        foreach ($parts as $i => $part) {
            list($part, $currentOffset) = $part;
            switch ($part) {
                case $this->delimiters['comment'][0]:
                    if (!$this->inRaw && !$in_tag) {
                        $in_comment = true;
                    }
                    break;

                case $this->delimiters['comment'][1]:
                    $in_comment = false;
                    break;

                case $this->delimiters['tag'][0]:
                    if (!$in_comment && !$in_string) {
                        //Check if we are not in a raw block or the next tag is an endraw
                        if (!$this->inRaw || trim($parts[$i + 1][0]) === $endraw) {
                            $in_tag                      = true;
                            $tagOpeningDelimiterPosition = $currentOffset;
                            $tagStartPosition            = $currentOffset + $tagOpeningDelimiterLength;
                        }
                    }
                    break;

                case $this->delimiters['tag'][1]:
                    if ($in_tag && !$in_string) {
                        $tag = trim(
                            substr(
                                $template,
                                $tagStartPosition,
                                $currentOffset - $tagStartPosition
                            )
                        );

                        $this->processText($template, $cursor, $tagOpeningDelimiterPosition);
                        $this->processTag($tag);

                        $cursor = $currentOffset + $tagClosingDelimiterLength;
                        $in_tag = false;
                    }
                    break;

                case '"':
                case "'":
                    if ($in_tag) {
                        if (!$in_string) {
                            $in_string = $part;
                        } elseif ($part === $in_string) {
                            $in_string = false;
                            //Let's walk from the previous character backwards
                            $i = $currentOffset;
                            while ($i > 0 && $template[--$i] === '\\') {
                                //If we find one backslash, we flip back the flag to true
                                //2 backslashes, flag is false... even = the string has ended
                                $in_string = !$in_string;
                            }
                            if ($in_string) {
                                //restore the variable value since it is
                                //either true or one of the delimiters
                                $in_string = $part;
                            }
                        }
                    }
                    break;
            }
        }

        if ($in_comment) {
            throw new SyntaxException('Unterminated comment', $this->line);
        }
        if ($in_string) {
            throw new SyntaxException('Unterminated string', $this->line);
        }
        if ($in_tag) {
            throw new SyntaxException('Unterminated tag', $this->line);
        }
        if ($this->inRaw) {
            throw new SyntaxException('Unterminated raw block', $this->line);
        }

        $this->processText($template, $cursor, strlen($template));
        $this->pushToken(Token::EOF);

        $this->tokens->rewind();

        return $this->tokens;
    }

    private function stripComments($text)
    {
        if ($this->inRaw) {
            //Don't strip comment-like text from raw blocks
            return $text;
        }
        // We can safely do this because $text contains no tags, thus no strings.
        if (($pos = strpos($text, $this->delimiters['comment'][0])) !== false) {
            $rpos     = strrpos($text, $this->delimiters['comment'][1]);
            $resumeAt = $rpos + strlen($this->delimiters['comment'][1]);
            $text     = substr($text, 0, $pos) . substr($text, $resumeAt);
        }

        return $text;
    }

    private function processText($template, $cursor, $endPosition)
    {
        $textLength = $endPosition - $cursor;
        if ($textLength === 0) {
            return;
        }

        $text         = substr($template, $cursor, $textLength);
        $strippedText = $this->stripComments($text);

        if ($strippedText !== '') {
            $this->pushToken(Token::TEXT, $strippedText);
        }
        $this->line += substr_count($text, "\n");
    }

    private function processTag($tag)
    {
        if (isset($this->blockEndingTags[$tag])) {
            if ($this->inRaw && $tag === $this->blockEndPrefix . 'raw') {
                $this->inRaw = false;
            } else {
                $this->pushToken(Token::TAG, $this->blockEndingTags[$tag]);
            }

            return;
        }

        foreach ($this->patternBasedTags as $tag_name => $unnamedTag) {
            if ($unnamedTag->matches($tag)) {
                $this->pushToken(Token::TAG, $tag_name);
                $unnamedTag->tokenize($this, $tag);

                return;
            }
        }

        $parts    = preg_split("/([ (\n\t])/", $tag, 2, PREG_SPLIT_DELIM_CAPTURE);
        $tag_name = $parts[0];

        if ($tag_name === 'raw') {
            $this->inRaw = true;

            return;
        }

        if (count($parts) === 3) {
            $expression = $parts[1] . $parts[2];
        } else {
            $expression = null;
        }

        if (!isset($this->tags[$tag_name])) {
            $tag_name   = $this->fallbackTagName;
            $expression = $tag;
        }

        if (!isset($this->tags[$tag_name])) {
            throw new ParseException("Unknown tag \"{$tag_name}\"", $this->line);
        }
        $tag = $this->tags[$tag_name];
        $tag->addNameToken($this);
        $tag->tokenize($this, $expression);
    }

    private function processToken($token)
    {
        if (in_array($token, $this->punctuation)) {
            $this->pushToken(Token::PUNCTUATION, $token);
        } elseif (in_array($token, $this->operators)) {
            $this->pushToken(Token::OPERATOR, $token);
        } elseif (strlen($token) !== 0) {
            $this->pushToken(Token::IDENTIFIER, $token);
        }
    }

    private function tokenizeLiteral($literal)
    {
        if (is_numeric($literal)) {
            $this->pushToken(Token::LITERAL, $literal);
        } else {
            switch ($literal[0]) {
                case '"':
                case "'":
                    //strip backslashes from double-slashes and escaped string delimiters
                    $stripSlashes = array(
                        "\\{$literal[0]}" => $literal[0],
                        '\\\\'            => '\\'
                    );
                    $literal      = strtr($literal, $stripSlashes);
                    $this->pushToken(Token::STRING, substr($literal, 1, -1));
                    break;

                case ':':
                    $this->pushToken(Token::STRING, ltrim($literal, ':'));
                    break;

                default:
                    switch (strtolower($literal)) {
                        case 'null':
                            $this->pushToken(Token::LITERAL, null);
                            break;
                        case 'true':
                            $this->pushToken(Token::LITERAL, true);
                            break;
                        case 'false':
                            $this->pushToken(Token::LITERAL, false);
                            break;
                    }
            }
        }
    }

    public function tokenizeExpression($expr)
    {
        if ($expr === null || $expr === '') {
            return;
        }
        $flags = PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY;
        foreach (preg_split($this->patterns['literal'], $expr, null, $flags) as $part) {
            if (preg_match($this->patterns['literal'], $part)) {
                $this->tokenizeLiteral($part);
                $this->line += substr_count($part, "\n");
            } else {
                foreach (preg_split($this->patterns['operator'], $part, null, $flags) as $subpart) {
                    if ($subpart !== ' ') {
                        $this->line += substr_count($subpart, "\n");
                        $this->processToken(trim($subpart));
                    }
                }
            }
        }
    }

    public function pushToken($type, $value = null)
    {
        $this->tokens->push(new Token($type, $value, $this->line));
    }
}
