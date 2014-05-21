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

    public function __construct(Environment $environment)
    {
        $blockNames = array();
        foreach ($environment->getTags() as $name => $tag) {
            if ($tag->hasEndingTag()) {
                $blockNames[] = $name;
            }

            if ($tag->isPatternBased()) {
                $this->patternBasedTags[$name] = $tag;
            } else {
                $this->tags[$name] = $tag;
            }
        }

        $this->delimiters = $environment->getOption(
            'delimiters',
            array(
                'tag'     => array('{', '}'),
                'comment' => array('{#', '#}')
            )
        );

        $this->fallbackTagName = $environment->getOption('fallback_tag', false);
        $this->blockEndPrefix  = $environment->getOption('block_end_prefix', 'end');
        $this->operators       = $environment->getOperatorSymbols();
        $this->punctuation     = array(',', '[', ']', '(', ')', ':', '?', '=>');

        $literals = array(
            'true',
            'false',
            'null',
            ':[a-zA-Z]+[a-zA-Z_\-0-9]*',
            '(?<!\w)\d+(?:\.\d+)?',
            '"(?:\\\\.|[^"\\\\])*"',
            "'(?:\\\\.|[^'\\\\])*'"
        );

        $blocks_pattern  = implode('|', $blockNames);
        $literal_pattern = implode('|', $literals);

        $blockEndPrefix = preg_quote($this->blockEndPrefix, '/');
        $this->patterns = array(
            'closing_tag' => "/{$blockEndPrefix}({$blocks_pattern}|raw)$/Ai",
            'operator'    => $this->getOperatorPattern(),
            'literal'     => "/({$literal_pattern})/i"
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

    private function preProcessTemplate($template)
    {
        $flags         = PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE;
        $pattern_parts = array();
        foreach ($this->delimiters as $delimiter) {
            $opening                 = preg_quote($delimiter[0], '/');
            $closing                 = preg_quote($delimiter[1], '/');
            $pattern                 = $opening . '|' . $closing;
            $pattern_parts[$pattern] = strlen($pattern);
        }
        arsort($pattern_parts);
        $pattern        = sprintf('/(%s|["\'])/', implode('|', array_keys($pattern_parts)));
        $parts          = preg_split($pattern, $template, -1, $flags);
        $matches        = array();
        $tag_just_ended = false;
        $in_comment     = false;
        $in_tag         = false;
        $in_string      = false;
        $tag            = '';
        $offset         = 0;
        $in_raw         = false;
        $line           = 1;
        $commentLines   = 0;

        $endraw                    = $this->blockEndPrefix . 'raw';
        $tagClosingDelimiterLength = strlen($this->delimiters['tag'][1]);

        foreach ($parts as $i => $part) {
            list($part, $currentOffset) = $part;
            switch ($part) {
                case $this->delimiters['comment'][0]:
                    if (!$in_raw) {
                        if (!$in_tag) {
                            $in_comment = true;
                        } else {
                            $tag .= $part;
                        }
                    }
                    break;

                case $this->delimiters['comment'][1]:
                    if ($in_comment) {
                        $in_comment = false;
                        $line += $commentLines;
                    } else {
                        $tag .= $part;
                    }
                    break;

                case $this->delimiters['tag'][0]:
                    if (!$in_comment) {
                        if ($in_raw) {
                            //Since this is a delimiter, $parts[$i + 1] always exists.
                            if (trim($parts[$i + 1][0]) === $endraw) {
                                $in_raw = false;
                                $in_tag = true;
                                $tag    = '';
                                $offset = $currentOffset;
                            }
                        } elseif (!$in_string) {
                            if ($in_tag) {
                                $tag = '';
                            }
                            $in_tag = true;
                            $offset = $currentOffset;
                        } else {
                            $tag .= $part;
                        }
                    }
                    break;

                case $this->delimiters['tag'][1]:
                    if ($in_tag) {
                        if (!$in_string) {
                            $in_tag         = false;
                            $tag_just_ended = true;
                        } else {
                            $tag .= $part;
                        }
                    }
                    break;

                case '"':
                case "'":
                    if ($in_tag) {
                        if (!$in_string) {
                            $in_string = $part;
                        } elseif ($part == $in_string) {
                            // odd number of backslashes means that the delimiter is escaped
                            if (strspn(strrev($tag), '\\') % 2 == 0) {
                                $in_string = false;
                            }
                        }
                        $tag .= $part;
                    }
                    break;

                default:
                    if ($in_tag) {
                        $tag .= $part;
                    }
                    if ($in_comment) {
                        $commentLines += substr_count($part, "\n");
                    } else {
                        $line += substr_count($part, "\n");
                    }
                    break;
            }
            if ($tag_just_ended) {
                $tag_just_ended = false;

                $tag = trim($tag);
                if ($tag == 'raw') {
                    $in_raw = true;
                }

                $matches[] = array(
                    $tag,
                    $currentOffset - $offset + $tagClosingDelimiterLength,
                    $offset
                );

                $tag = '';
            }
        }

        if ($in_comment) {
            throw new SyntaxException('Unterminated comment', $line);
        }
        if ($in_string) {
            throw new SyntaxException('Unterminated string', $line);
        }
        if ($in_tag) {
            throw new SyntaxException('Unterminated tag', $line);
        }
        if ($in_raw) {
            throw new SyntaxException('Unterminated raw block', $line);
        }

        return $matches;
    }

    public function tokenize($template)
    {
        $this->line   = 1;
        $this->tokens = new Stream();
        $this->inRaw  = false;

        $cursor = 0;
        foreach ($this->preProcessTemplate($template) as $match) {
            list($tag, $tagLength, $tagPosition) = $match;

            $this->processText($template, $cursor, $tagPosition);
            $this->processTag($tag);

            $cursor = $tagPosition + $tagLength;
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
        $match = array();
        if (preg_match($this->patterns['closing_tag'], $tag, $match)) {
            if ($this->inRaw) {
                $this->inRaw = false;
            } else {
                $this->pushToken(Token::TAG, 'end' . $match[1]);
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
