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
    private $operators;
    private $delimiters;

    /**
     * @var Tag[]
     */
    private $tags;
    private $patterns;
    private $in_string;
    private $punctuation;

    public function __construct(Environment $environment)
    {
        $blocknames = array();
        foreach ($environment->getTags() as $tag) {
            $name = $tag->getTag();
            if ($tag->hasEndingTag()) {
                $blocknames[] = $name;
            }
            $this->tags[$name] = $tag;
        }

        $options          = $environment->getOptions();
        $this->delimiters = $options['delimiters'];

        $literals = array('true', 'false', 'null', ':[a-zA-Z]+[a-zA-Z_\-0-9]*', '\d+(?:\.\d+)?');

        $blocks_pattern  = implode('|', $blocknames);
        $literal_pattern = implode('|', $literals);

        $this->patterns = array(
            'assignment'  => '/(.*?)\s*:\s*(.*?)$/ADsu',
            'closing_tag' => sprintf('/end(%s)/Ai', $blocks_pattern),
            'operator'    => $this->getOperatorPattern($environment),
            'literal'     => sprintf('/(%s)/i', $literal_pattern)
        );
    }

    private function fetchOperators(Environment $environment)
    {
        $this->operators = array();
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
        $this->punctuation = array(',', '[', ']', '(', ')', ':', '?', '=>', "'", '"');

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

    private function findTags($template)
    {
        $flags         = PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE;
        $pattern_parts = array();
        foreach ($this->delimiters as $delimiter) {
            $opening         = preg_quote($delimiter[0], '/');
            $closing         = preg_quote($delimiter[1], '/');
            $pattern_parts[] = $opening . '|' . $closing;
        }
        $pattern        = sprintf('/(%s|["\'])/', implode('|', $pattern_parts));
        $parts          = preg_split($pattern, $template, -1, $flags);
        $matches        = array();
        $tag_just_ended = false;
        $in_comment     = false;
        $in_tag         = false;
        $in_string      = false;
        $tag            = '';
        $offset         = 0;
        $in_raw         = false;
        foreach ($parts as $i => $part) {
            list($part, $off) = $part;
            switch ($part) {
                case $this->delimiters['comment'][0]:
                    if (!$in_tag && !$in_raw) {
                        $in_comment = true;
                    }
                    break;
                case $this->delimiters['comment'][1]:
                    if ($in_comment) {
                        $in_comment = false;
                    }
                    break;
                case $this->delimiters['tag'][0]:
                    if (!$in_comment) {
                        if ($in_raw) {
                            if (isset($parts[$i]) && trim($parts[$i + 1][0]) === 'endraw') {
                                $in_raw = false;
                                $in_tag = true;
                                $tag    = '';
                            }
                        } elseif (!$in_string) {
                            if ($in_tag) {
                                $tag = '';
                            } else {
                                $in_tag = true;
                            }
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
                            if (substr($tag, -1) !== '\\') {
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
                if (trim($tag) == 'raw') {
                    $in_raw = true;
                } elseif (trim($tag) !== 'endraw') {
                    $matches[1][] = array(trim($tag), $offset);
                    $matches[0][] = array($this->delimiters['tag'][0] . $tag . $this->delimiters['tag'][1], $offset);
                }
                $tag = '';
            }
            $tag_just_ended = false;
        }
        return $matches;
    }

    private function stripComments($text)
    {
        // We can safely do this because $text contains no tags, thus no strings.
        if (($pos = strpos($text, $this->delimiters['comment'][0])) !== false) {
            $rpos = strrpos($text, $this->delimiters['comment'][1]);
            $text = substr($text, 0, $pos) . substr($text, $rpos + strlen($this->delimiters['comment'][1]));
        }
        return $text;
    }

    public function tokenize($template)
    {
        $this->line   = 1;
        //init states
        $this->tokens = array();
        unset($this->in_string);

        $matches = $this->findTags($template);
        $cursor  = 0;
        foreach ($matches[0] as $position => $match) {
            list($tag, $tag_position) = $match;

            $text_length = $tag_position - $cursor;
            $text        = substr($template, $cursor, $text_length);

            $text_lines = substr_count($text, "\n");

            $this->pushToken(Token::TEXT, $this->stripComments($text));

            $cursor = $tag_position;
            $this->line += $text_lines;

            $tag_expr = $matches[1][$position][0];

            if (!$this->processAssignment($tag_expr)) {
                $this->processTag($tag_expr);
            }
            $cursor += strlen($tag);
            $this->line += substr_count($tag, "\n");
        }

        if ($cursor < strlen($template)) {
            $text = substr($template, $cursor);
            $this->pushToken(Token::TEXT, $this->stripComments($text));
        }
        if (isset($this->in_string)) {
            $message = sprintf('Unterminated string found in line %d', $this->line);
            throw new SyntaxException($message);
        }
        $this->pushToken(Token::EOF);
        return new Stream($this->tokens);
    }

    private function processTag($tag)
    {
        $match = array();
        if (preg_match($this->patterns['closing_tag'], $tag, $match)) {

            $type = $match[1];
            $this->pushToken(Token::TAG, 'end' . $type);
            return true;
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
        }

        if (isset($this->tags[$tag_name])) {
            $tag = $this->tags[$tag_name];
            $this->pushToken(Token::TAG, $tag_name);
            $tag->tokenizeExpression($this, $expression);
        } else {
            $this->pushToken(Token::EXPRESSION_START);
            $this->tokenizeExpression($tag_name);
            $this->tokenizeExpression($expression);
            $this->pushToken(Token::EXPRESSION_END);
        }
        return true;
    }

    private function processAssignment($tag)
    {
        $match = array();
        if (!preg_match($this->patterns['assignment'], $tag, $match)) {
            return false;
        }
        $identifier = $match[1];
        $expression = $match[2];

        if (!preg_match('/^([a-zA-Z])+[a-zA-Z0-9\_]*$/i', $identifier)) {
            return false;
        }
        if (preg_match($this->patterns['literal'], $identifier)) {
            return false;
        }

        $this->pushToken(Token::TAG, 'assign');
        $this->pushToken(Token::IDENTIFIER, $identifier);
        $this->pushToken(Token::EXPRESSION_START);
        $this->tokenizeExpression($expression);
        $this->pushToken(Token::EXPRESSION_END);
        return true;
    }

    public function startString($delimiter)
    {
        $this->in_string = array(
            'string'    => '',
            'delimiter' => $delimiter
        );
    }

    private function processString($token)
    {
        if ($token === $this->in_string['delimiter']) {
            if (substr($this->in_string['string'], -1) !== '\\') {
                $this->pushToken(Token::STRING, $this->in_string['string']);
                unset($this->in_string);
                return;
            } else {
                $this->in_string['string'] = substr($this->in_string['string'], 0, -1);
            }
        }
        $this->in_string['string'] .= $token;
    }

    public function processToken($token)
    {
        if (isset($this->in_string)) {
            $this->processString($token);
        } elseif ($token == '"' || $token == "'") {
            $this->startString($token);
        } else {
            $token = trim($token);
            if (in_array($token, $this->punctuation)) {
                $this->pushToken(Token::PUNCTUATION, $token);
            } elseif (in_array($token, $this->operators)) {
                $this->pushToken(Token::OPERATOR, $token);
            } elseif (strlen($token) !== 0) {
                $this->pushToken(Token::IDENTIFIER, $token);
            }
        }
    }

    private function tokenizeLiteral($literal)
    {
        if (isset($this->in_string)) {
            $this->in_string['string'] .= $literal;
        } elseif (is_numeric($literal)) {
            $this->pushToken(Token::LITERAL, $literal);
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
                default:
                    $this->pushToken(Token::STRING, ltrim($literal, ':'));
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

    //Utilities

    public function pushToken($type, $value = null)
    {
        if ($type === Token::TEXT) {
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
