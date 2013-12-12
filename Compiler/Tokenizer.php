<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler;

use Modules\Templating\Compiler\Exceptions\SyntaxException;

class Tokenizer
{
    const STATE_TEXT           = 0;
    const STATE_STRING         = 1;
    const STATE_POSSIBLE_FLOAT = 2;
    const STATE_RAW            = 3;
    const STATE_COMMENT        = 4;

    private static $exceptions = array(
        'unexpected'            => 'Unexpected %s found in line %d',
        'unexpected_eof'        => 'Unexpected end of file found in line %d',
        'unexpected_tag'        => 'Unexpected %s tag found in line %d',
        'unexpected_ending_tag' => 'Unexpected closing tag %s found in line %d',
        'unterminated'          => 'Unterminated %s found in line %d'
    );
    private $tokens;
    private $operators;

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

        $this->literals = array('true', 'false', 'null');

        $blocks_pattern  = implode('|', $blocknames);
        $literal_pattern = implode('|', $this->literals);

        $this->patterns = array(
            'assignment'  => '/(.*?)\s*:\s*(.*?)$/ADsu',
            'tag'         => "/{\s*(#.*#|(?:'.*(?<!\\\)'|\".*(?<!\\\)\"|(?>[^{}])|(?R))+)\s*}(?:\n?)/m",
            'closing_tag' => sprintf('/end(raw|%s)/Ai', $blocks_pattern),
            'operator'    => $this->getOperatorPattern($environment),
            'literal'     => sprintf('/(%s|\d+(?:\.\d+)?)/Ai', $literal_pattern)
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
        $this->punctuation = array(',', '[', ']', '(', ')', ':', '?');
        $punctuation       = $quote(',[]():"\'?');
        arsort($operators);
        return sprintf('/(%s|[%s])/i', implode('|', array_keys($operators)), $punctuation);
    }

    public function tokenize($template)
    {
        $this->line        = 1;
        //init states
        $this->state_stack = array();
        $this->tokens      = array();

        $this->pushState(self::STATE_TEXT);
        $matches = array();
        preg_match_all($this->patterns['tag'], $template, $matches, PREG_OFFSET_CAPTURE);

        $cursor = 0;
        foreach ($matches[0] as $position => $match) {
            list($tag, $tag_position) = $match;
            $length = $tag_position - $cursor;

            $text = substr($template, $cursor, $length);

            $this->line += substr_count($text, "\n");
            
            $cursor += strlen($text);
            $cursor += strlen($tag);

            if (strpos($tag, '{#') === 0) {
                $this->pushState(self::STATE_COMMENT);
            }
            if ($this->isState(self::STATE_COMMENT)) {
                if (strrpos($tag, '#}') === strlen($tag) - 3) {
                    $this->popState();
                }
                continue;
            }
            $this->pushToken(Token::TEXT, $text);
            $tag_expr = $matches[1][$position][0];

            $this->line += substr_count($tag, "\n");
            if (!$this->processAssignment($tag_expr)) {
                $this->processTag($tag_expr);
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
            if (!$this->isState(self::STATE_RAW, ($type == 'raw'))) {
                $this->pushToken(Token::TAG, 'end' . $type);
            }
            return true;
        }

        if ($this->isState(self::STATE_RAW)) {
            $this->pushToken(Token::TEXT, $tag);
            return true;
        }

        $parts = preg_split('/([\ \(])/', $tag, 2, PREG_SPLIT_DELIM_CAPTURE);
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
        if ($tag_name === 'raw') {
            $this->pushState(self::STATE_RAW);
            return true;
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
        if (in_array($identifier, $this->literals)) {
            return false;
        }

        $this->pushToken(Token::TAG, 'assign');
        $this->pushToken(Token::IDENTIFIER, $identifier);
        $this->pushToken(Token::EXPRESSION_START);
        $this->tokenizeExpression($expression);
        $this->pushToken(Token::EXPRESSION_END);
        return true;
    }

    private function tokenizeLiteralOrIdentifier($token)
    {
        if ($this->isState(self::STATE_POSSIBLE_FLOAT)) {
            $this->popState();
            if (is_numeric($token) && $token > 0) {
                array_pop($this->stream); //.
                $integer_part = array_pop($this->stream)->getValue();
                $this->pushToken(Token::LITERAL, $integer_part . '.' . $token);
            }
        } elseif (is_numeric($token)) {
            $this->pushToken(Token::LITERAL, $token);
        } else {
            switch (strtolower($token)) {
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
                    $this->pushToken(Token::IDENTIFIER, $token);
            }
        }
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
                $this->tokenizeLiteralOrIdentifier($token);
            }
        }
    }

    public function tokenizeExpression($expr)
    {
        if ($expr === null || $expr === '') {
            return;
        }

        $parts = preg_split($this->patterns['operator'], $expr, null, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        array_walk($parts, array($this, 'processToken'));
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
