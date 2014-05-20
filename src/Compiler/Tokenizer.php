<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler;

use Modules\Templating\Compiler\Exceptions\SyntaxException;
use Modules\Templating\Environment;

class Tokenizer
{
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

        $this->delimiters      = $environment->getOption(
            'delimiters',
            array(
                'tag'     => array('{', '}'),
                'comment' => array('{#', '#}')
            )
        );
        $this->fallbackTagName = $environment->getOption('fallback_tag', false);
        $this->blockEndPrefix  = $environment->getOption('block_end_prefix', 'end');

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
            'operator'    => $this->getOperatorPattern($environment),
            'literal'     => "/({$literal_pattern})/i"
        );
    }

    private function fetchOperators(Environment $environment)
    {
        foreach ($environment->getOperatorSymbols() as $operator) {

            if (!is_array($operator)) {
                $this->operators[] = $operator;
            } else {
                foreach ($operator as $symbol) {
                    $this->operators[] = $symbol;
                }
            }
        }
    }

    private function getOperatorPattern(Environment $environment)
    {
        $this->punctuation = array(',', '[', ']', '(', ')', ':', '?', '=>');

        $quote = function ($operator) {
            if (preg_match('/^[a-zA-Z\ ]+$/', $operator)) {
                return '(?<=^|[^\w])' . preg_quote($operator, '/') . '(?=[\s()\[\]]|$)';
            } else {
                return preg_quote($operator, '/');
            }
        };

        $this->fetchOperators($environment);

        $operators = array();
        $signs     = '';

        $symbols = array_merge($this->operators, $this->punctuation);
        foreach ($symbols as $symbol) {
            $length = strlen($symbol);
            if ($length == 1) {
                $signs .= $symbol;
            } else {
                $symbol             = $quote($symbol);
                $operators[$symbol] = $length;
            }
        }
        arsort($operators);

        return sprintf('/(%s|[%s ])/i', implode('|', array_keys($operators)), $quote($signs));
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
        $matches        = array(array(), array());
        $tag_just_ended = false;
        $in_comment     = false;
        $in_tag         = false;
        $in_string      = false;
        $tag            = '';
        $offset         = 0;
        $in_raw         = false;

        $endraw = $this->blockEndPrefix . 'raw';
        foreach ($parts as $i => $part) {
            list($part, $off) = $part;
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
                    } else {
                        $tag .= $part;
                    }
                    break;

                case $this->delimiters['tag'][0]:
                    if (!$in_comment) {
                        if ($in_raw) {
                            if (isset($parts[$i]) && trim($parts[$i + 1][0]) === $endraw) {
                                $in_raw = false;
                                $in_tag = true;
                                $tag    = '';
                                $offset = $off;
                            }
                        } elseif (!$in_string) {
                            if ($in_tag) {
                                $tag = '';
                            }
                            $in_tag = true;
                            $offset = $off;
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
                    break;
            }
            if ($tag_just_ended) {
                $tag_just_ended = false;
                if (trim($tag) == 'raw') {
                    $in_raw = true;
                }

                $matches[1][] = array(trim($tag), $offset);
                $matches[0][] = array(
                    $this->delimiters['tag'][0] . $tag . $this->delimiters['tag'][1],
                    $offset
                );

                $tag = '';
            }
        }

        if ($in_comment) {
            throw new SyntaxException('Unterminated comment found');
        }
        if ($in_string) {
            throw new SyntaxException('Unterminated string found');
        }
        if ($in_tag) {
            throw new SyntaxException('Unterminated tag found');
        }
        if ($in_raw) {
            throw new SyntaxException('Unterminated raw block found');
        }

        return $matches;
    }

    private function stripComments($text)
    {
        if ($this->inRaw) {
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

    public function tokenize($template)
    {
        $this->line = 1;
        //init states
        $this->tokens = array();
        $this->inRaw  = false;

        $matches = $this->preProcessTemplate($template);
        $cursor  = 0;
        foreach ($matches[0] as $position => $match) {
            list($tag, $tag_position) = $match;

            $text_length = $tag_position - $cursor;
            $text        = substr($template, $cursor, $text_length);

            $text_lines = substr_count($text, "\n");

            $this->pushToken(Token::TEXT, $text);

            $cursor = $tag_position;
            $this->line += $text_lines;

            $tag_expr = $matches[1][$position][0];

            if (!$this->processTag($tag_expr)) {
                $this->pushToken(Token::TEXT, $tag_expr);
            }
            $cursor += strlen($tag);
            $this->line += substr_count($tag, "\n");
        }

        if ($cursor < strlen($template)) {
            $text = substr($template, $cursor);
            $this->pushToken(Token::TEXT, $text);
        }
        $this->pushToken(Token::EOF);

        return new Stream($this->tokens);
    }

    private function processTag($tag)
    {
        $match = array();
        if (preg_match($this->patterns['closing_tag'], $tag, $match)) {
            if (!$this->inRaw) {
                $this->pushToken(Token::TAG, 'end' . $match[1]);

                return true;
            }
            if ($match[1] === 'raw') {
                $this->inRaw = false;

                return true;
            }
        }

        if ($this->inRaw) {
            return false;
        }

        foreach ($this->patternBasedTags as $tag_name => $unnamedTag) {
            if ($unnamedTag->matches($tag)) {
                $this->pushToken(Token::TAG, $tag_name);
                $unnamedTag->tokenize($this, $tag);

                return true;
            }
        }

        $parts = preg_split('/([ (])/', $tag, 2, PREG_SPLIT_DELIM_CAPTURE);
        switch (count($parts)) {
            case 1:
                $tag_name   = $parts[0];
                $expression = null;
                break;

            case 3:
                $tag_name   = $parts[0];
                $expression = $parts[1] . $parts[2];
                break;

            default:
                $tag_name   = null;
                $expression = $tag;
                break;
        }

        if ($tag_name === 'raw') {
            $this->inRaw = true;

            return true;
        }

        if (!isset($tag_name) || !isset($this->tags[$tag_name])) {
            $tag_name   = $this->fallbackTagName;
            $expression = $tag;
        }
        if (!isset($this->tags[$tag_name])) {
            throw new SyntaxException("Unknown tag {$tag_name} in line {$this->line}");
        }
        $tag = $this->tags[$tag_name];
        $tag->addNameToken($this);
        $tag->tokenize($this, $expression);

        return true;
    }

    public function processToken($token)
    {
        $token = trim($token);
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
        } elseif ($literal[0] === '"' || $literal[0] === "'") {
            $this->pushToken(Token::STRING, substr($literal, 1, -1));
        } elseif ($literal[0] === ':') {
            $this->pushToken(Token::STRING, ltrim($literal, ':'));
        } else {
            switch (strtolower($literal)) {
                case 'null':
                case 'none':
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

    public function tokenizeExpression($expr)
    {
        if ($expr === null || $expr === '') {
            return;
        }
        $flags = PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY;
        $parts = preg_split($this->patterns['literal'], $expr, null, $flags);
        foreach ($parts as $part) {
            if (preg_match($this->patterns['literal'], $part)) {
                $this->tokenizeLiteral($part);
            } else {
                $subparts = preg_split($this->patterns['operator'], $part, null, $flags);
                array_walk($subparts, array($this, 'processToken'));
            }
        }
    }

    public function pushToken($type, $value = null)
    {
        if ($type === Token::TEXT) {
            $value = $this->stripComments($value);
            if ($value === '') {
                return;
            }
            $end = end($this->tokens);
            if ($end && $end->test($type)) {
                $old   = array_pop($this->tokens)->getValue();
                $value = $old . $value;
            }
        }
        $this->tokens[] = new Token($type, $value, $this->line);
    }
}
