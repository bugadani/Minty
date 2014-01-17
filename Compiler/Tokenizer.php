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
    const STATE_TEXT   = 0;
    const STATE_STRING = 1;

    private static $exceptions = array(
        'unexpected'            => 'Unexpected %s found in line %d',
        'unexpected_eof'        => 'Unexpected end of file found in line %d',
        'unexpected_tag'        => 'Unexpected %s tag found in line %d',
        'unexpected_ending_tag' => 'Unexpected closing tag %s found in line %d',
        'unterminated'          => 'Unterminated %s found in line %d'
    );
    private $tokens;
    private $operators;
    private $delimiters;

    /**
     * @var Tag[]
     */
    private $tags;
    private $patterns;
    private $state_stack;
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

        $literals = array('true', 'false', 'null', ':[a-zA-Z]+[a-zA-Z_\-0-9]*');

        $blocks_pattern  = implode('|', $blocknames);
        $literal_pattern = implode('|', $literals);

        $this->patterns = array(
            'assignment'  => '/(.*?)\s*:\s*(.*?)$/ADsu',
            'closing_tag' => sprintf('/end(%s)/Ai', $blocks_pattern),
            'operator'    => $this->getOperatorPattern($environment),
            'literal'     => sprintf('/(%s|\d+(?:\.\d+)?)/i', $literal_pattern)
        );
    }

    private function getOperatorPattern(Environment $environment)
    {
        $this->operators = array();
        $operators       = array();

        $quote = function ($operator) {
            if (preg_match('/[a-zA-Z\ ]/', $operator)) {
                return '(?<=^|[^\w])' . preg_quote($operator, '/') . '(?=[\s()\[\]]|$)';
            } else {
                return preg_quote($operator, '/');
            }
        };

        foreach ($environment->getOperatorSymbols() as $operator) {
            if (!is_array($operator)) {
                $operator = array($operator);
            }

            foreach ($operator as $symbol) {
                $this->operators[]  = $symbol;
                $symbol             = $quote($symbol);
                $operators[$symbol] = strlen($symbol);
            }
        }
        $this->punctuation = array(',', '[', ']', '(', ')', ':', '?', '=>');
        $punctuation       = $quote(',[]():"\'?');
        arsort($operators);
        return sprintf('/(=>|%s|[%s ])/i', implode('|', array_keys($operators)), $punctuation);
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
                        } else {
                            if (!$in_string) {
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

    public function tokenize($template)
    {
        $this->line        = 1;
        //init states
        $this->state_stack = array();
        $this->tokens      = array();

        $this->pushState(self::STATE_TEXT);
        $matches = $this->findTags($template);
        $cursor  = 0;
        foreach ($matches[0] as $position => $match) {
            list($tag, $tag_position) = $match;
            $length = $tag_position - $cursor;

            $text = substr($template, $cursor, $length);

            $this->line += substr_count($text, "\n");

            $cursor += strlen($text);
            $cursor += strlen($tag);

            // We can safely do this because $text contains no tags, thus no strings.
            if (($pos = strpos($text, $this->delimiters['comment'][0])) !== false) {
                $rpos = strrpos($text, $this->delimiters['comment'][1]);
                $text = substr($text, 0, $pos) . substr($text, $rpos + strlen($this->delimiters['comment'][1]));
            }

            $this->pushToken(Token::TEXT, $text);
            $tag_expr = $matches[1][$position][0];

            $this->line += substr_count($tag, "\n");
            if (!$this->processAssignment($tag_expr)) {
                if (!$this->processTag($tag_expr)) {
                    $this->pushToken(Token::TEXT, $tag);
                }
            }
        }

        if ($cursor < strlen($template)) {
            $text = substr($template, $cursor);
            $this->pushToken(Token::TEXT, $text);
        }
        if (count($this->state_stack) > 1) {
            if ($this->isState(self::STATE_STRING)) {
                $this->throwException('unterminated', 'string');
            }
            $this->throwException('unexpected_eof');
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
        $array = array(
            'string'    => '',
            'delimiter' => $delimiter
        );
        $this->pushState(self::STATE_STRING, $array);
    }

    private function processString($token)
    {
        $state_value = $this->getStateValue();
        if ($token === $state_value['delimiter']) {
            if (substr($state_value['string'], -1) !== '\\') {
                $this->pushToken(Token::STRING, $state_value['string']);
                $this->popState();
                return;
            } else {
                $state_value['string'] = substr($state_value['string'], 0, -1);
            }
        }
        $state_value['string'] .= $token;
        $this->popState();
        $this->pushState(self::STATE_STRING, $state_value);
    }

    public function processToken($token)
    {
        if ($this->isState(self::STATE_STRING)) {
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
        if (is_numeric($literal)) {
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
    public function throwException($type)
    {
        $args   = func_get_args();
        array_shift($args);
        $args[] = $this->line;
        throw new SyntaxException(vsprintf(self::$exceptions[$type], $args));
    }

    public function getState()
    {
        $state = end($this->state_stack);
        return $state['state'];
    }

    public function getStateValue()
    {
        $state = end($this->state_stack);
        return $state['value'];
    }

    public function pushState($state, $value = null)
    {
        $this->state_stack[] = array(
            'state' => $state,
            'line'  => $this->line,
            'value' => $value
        );
    }

    public function popState()
    {
        if (empty($this->state_stack)) {
            throw new SyntaxException('Unexpected closing tag in line ' . $this->line);
        }
        array_pop($this->state_stack);
    }

    public function isState($checked, $pop = false)
    {
        if (is_array($checked)) {
            $args    = $checked;
            $checked = array_shift($args);
        }
        if ($checked !== $this->getState()) {
            return false;
        }
        if ($pop) {
            $this->popState();
        }
        return true;
    }

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
