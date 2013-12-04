<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler;

class Parser
{
    const STATE_TEXT           = 0;
    const STATE_BLOCK          = 1;
    const STATE_EXPRESSION     = 2;
    const STATE_STRING         = 3;
    const STATE_ARGUMENT_LIST  = 4;
    const STATE_ARRAY          = 5;
    const STATE_ASSIGNMENT     = 6;
    const STATE_POSSIBLE_FLOAT = 7;
    const STATE_TAG            = 8;

    private static $exceptions = array(
        'unexpected'          => 'Unexpected %s found in line %d',
        'unexpected_operator' => 'Unexpected %s operator found in line %d',
        'unexpected_tag'      => 'Unexpected %s tag found in line %d',
        'unterminated'        => 'Unterminated %s in line %d',
        'invalid_identifier'  => 'Invalid identifier %s found in line %d',
        'unexpected_eof'      => 'Unexpected end of file. Unterminated %s in line %d'
    );
    private static $states     = array(
        self::STATE_TEXT           => 'text',
        self::STATE_BLOCK          => 'block',
        self::STATE_EXPRESSION     => 'expression',
        self::STATE_ARGUMENT_LIST  => 'argument list',
        self::STATE_STRING         => 'string',
        self::STATE_ARRAY          => 'array index',
        self::STATE_ASSIGNMENT     => 'assignment',
        self::STATE_POSSIBLE_FLOAT => 'possible float',
        self::STATE_TAG            => 'tag'
    );

    /**
     * @var string[]
     */
    private $patterns;

    /**
     * @var Tag[]
     */
    private $tags;

    /**
     * @var string[]
     */
    private $operators;

    /**
     * @var Operator[]
     */
    private $operator_parsers;

    /**
     * @var string[]
     */
    private $keywords;

    /**
     * @var string[]
     */
    private $literals;

    /**
     * @var TokenStream
     */
    private $stream;
    private $state_stack;
    private $line;

    public function __construct(TemplateDescriptor $descriptor)
    {
        $this->operator_parsers = $descriptor->operators();

        $blocknames = array();
        foreach ($descriptor->tags() as $tag) {
            $name = $tag->getTag();
            if ($tag->hasEndingTag()) {
                $blocknames[] = $name;
            }
            $this->tags[$name] = $tag;
        }

        $this->keywords = array('in', 'set', 'using', 'not in');
        $this->literals = array('true', 'false', 'null');

        $blocks_pattern  = implode('|', $blocknames);
        $literal_pattern = implode('|', $this->literals);

        $this->patterns = array(
            'comment'     => '/({\*.*?(?<!\\\)\*})/su',
            'assignment'  => '/(.*?)\s*:\s*(.*?)$/ADsu',
            'tag'         => "/{\s*((?:(?>[^{}])|(?R))+)\s*}(?:\n?)/",
            'closing_tag' => sprintf('/end(raw|%s)/Ai', $blocks_pattern),
            'operator'    => $this->getOperatorPattern(),
            'literal'     => sprintf('/(%s|\d+(?:\.\d+)?)/Ai', $literal_pattern)
        );
    }

    private function getOperatorPattern()
    {
        $operators = array();

        $quote = function ($operator) {
            if (preg_match('/[a-zA-Z\ ]/', $operator)) {
                return '(?<=^|[^\w])' . preg_quote($operator, '/') . '(?=[\s()\[\]])';
            } else {
                return preg_quote($operator, '/');
            }
        };

        foreach ($this->operator_parsers as $parser) {
            $parsed = $parser->operators();
            if (!is_array($parsed)) {
                $parsed = array($parsed);
            }

            foreach ($parsed as $operator) {
                $this->operators[]    = $operator;
                $operator             = $quote($operator);
                $operators[$operator] = strlen($operator);
            }
        }
        foreach ($this->keywords as $keyword) {
            $keyword             = $quote($keyword);
            $operators[$keyword] = strlen($keyword);
        }
        arsort($operators);
        return sprintf('/(%s)/i', implode('|', array_keys($operators)));
    }

    public function parse($code)
    {
        $this->line        = 1;
        $this->stream      = new TokenStream();
        //init states
        $this->state_stack = array(array(
                'state' => self::STATE_TEXT,
                'value' => null
        ));

        $matches = array();
        $code    = preg_replace($this->patterns['comment'], '', $code);
        preg_match_all($this->patterns['tag'], $code, $matches, PREG_OFFSET_CAPTURE);

        $cursor = 0;
        foreach ($matches[0] as $position => $match) {
            list($tag, $tag_position) = $match;
            $length = $tag_position - $cursor;

            $text = substr($code, $cursor, $length);
            $this->pushToken(Token::TEXT, $text);

            $this->line += substr_count($text, "\n");
            $this->line += substr_count($tag, "\n");

            $cursor += strlen($text);
            $cursor += strlen($tag);

            $tag_expr = $matches[1][$position][0];

            $is_tag_processed = $this->parseTag($tag_expr);
            if ($is_tag_processed === null) {
                if (!$this->parseAssignment($tag_expr)) {
                    $this->parseExpression($tag_expr, '{', '}');
                }
            } elseif ($is_tag_processed === false) {
                $this->pushToken(Token::TEXT, $matches[0][$position][0]);
            }
        }

        $text = substr($code, $cursor);
        $this->pushToken(Token::TEXT, $text);
        $this->line += substr_count($text, "\n");

        if (count($this->state_stack) > 1) {
            $this->throwException('unexpected_eof', $this->getStateString());
        }
        $this->pushToken(Token::EOF);
        return $this->stream;
    }

    private function parseClosingTag($type)
    {
        if ($this->isState(self::STATE_BLOCK, 'raw', ($type == 'raw'))) {
            return true;
        }
        if (!$this->isState(self::STATE_BLOCK, $type, true)) {
            $this->throwException('unexpected', 'closing tag end' . $type . ' in state ');
        }
        $this->pushToken(Token::BLOCK_END, $type);
        return true;
    }

    private function getTag($tag_name)
    {
        if ($tag_name === 'raw') {
            $this->pushState(self::STATE_BLOCK, 'raw');
            return true;
        }
        if (isset($this->tags[$tag_name])) {
            $tag = $this->tags[$tag_name];
            if ($tag->hasEndingTag()) {
                $this->pushState(self::STATE_BLOCK, $tag_name);
                $this->pushToken(Token::BLOCK_START, $tag_name);
            } else {
                $this->pushToken(Token::TAG, $tag_name);
            }
        } else {
            return null;
        }
        return $tag;
    }

    private function parseTag($tag_name)
    {
        $match = array();
        if (preg_match($this->patterns['closing_tag'], $tag_name, $match)) {
            return $this->parseClosingTag($match[1]);
        }
        if ($this->isState(self::STATE_BLOCK, 'raw')) {
            return false;
        }

        if (strpos($tag_name, ' ') !== false) {
            list($tag_name, $expr) = explode(' ', $tag_name, 2);
            $expression = trim($expr);
        } else {
            $expression = null;
        }

        $tag = $this->getTag($tag_name);
        if (!$tag instanceof Tag) {
            return $tag;
        }

        $states = $tag->requiresState();
        if (!empty($states)) {
            $filtered = array_filter($states, array($this, 'isState'));
            if (empty($filtered)) {
                $this->throwException('unexpected_tag', $tag_name);
            }
        }
        $tag->setExpectations($this->stream);
        $tag->parseExpression($this, $expression);
        return true;
    }

    public function parseString($token)
    {
        $state_value = $this->getStateValue();
        if ($token === $state_value['delimiter']) {
            if (substr($state_value['string'], -1) !== '\\') {
                $this->pushToken(Token::STRING, $state_value['string']);
                $this->popState();
                $this->stream->expect(Token::LITERAL, null, 1, true)
                        ->also(Token::IDENTIFIER, null, 1, true)
                        ->also(Token::STRING, null, 1, true);
                return;
            }
        }
        $state_value['string'] .= $token;
        $this->popState();
        $this->pushState(self::STATE_STRING, $state_value);
    }

    private function parseAssignment($tag)
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

        $this->pushToken(Token::KEYWORD, 'assign');
        $this->pushToken(Token::IDENTIFIER, $identifier);
        $this->pushState(self::STATE_ASSIGNMENT);
        $this->parseExpression($expression, '{', '}');
        $this->popState();
        return true;
    }

    public function parseToken($token)
    {
        if ($this->isState(self::STATE_STRING)) {
            $this->parseString($token);
        } else {
            $token = trim($token);
            if (in_array($token, $this->keywords)) {
                $this->parseKeyword($token);
            } elseif (preg_match($this->patterns['literal'], $token)) {
                $this->parseLiteralOrIdentifier(Token::LITERAL, $token);
            } elseif (in_array($token, $this->operators)) {
                $this->parseOperator($token);
            } elseif (!empty($token)) {
                $this->parseLiteralOrIdentifier(Token::IDENTIFIER, $token);
            }
        }
    }

    public function parseExpression($expr, $start = '(', $end = ')')
    {
        if ($expr === null) {
            return;
        }
        $this->pushState(self::STATE_EXPRESSION);
        $this->pushToken(Token::EXPRESSION_START, $start);
        $parts = preg_split($this->patterns['operator'], $expr, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);

        array_walk($parts, array($this, 'parseToken'));

        $this->checkEndState();
        $this->pushToken(Token::EXPRESSION_END, $end);
        $this->popState();
    }

    private function checkEndState()
    {
        if ($this->isState(self::STATE_STRING)) {
            $this->throwException('unterminated', 'string');
        }
        if ($this->isState(self::STATE_ARRAY, '.', true)) {
            if ($this->stream->test(Token::OPERATOR, '.')) {
                $this->throwException('unexpected', 'period');
            }
        }
    }

    //Expression parts
    private function parseOperator($operator)
    {
        if ($this->isState(self::STATE_POSSIBLE_FLOAT)) {
            $this->popState();
        }
        foreach ($this->operator_parsers as $parser) {
            if ($parser->parse($this, $operator)) {
                return;
            }
        }
        $this->throwException('unexpected_operator', $operator);
    }

    private function parseKeyword($token)
    {
        if ($this->isState(self::STATE_POSSIBLE_FLOAT)) {
            $this->popState();
        }
        if (!$this->isState(self::STATE_EXPRESSION)) {
            $this->throwException('unexpected_token', 'keyword');
        }
        $this->pushToken(Token::KEYWORD, $token);
        switch ($token) {
            case 'in':
            case 'not in':
                $this->stream->expect(Token::IDENTIFIER);
                $this->stream->expect(Token::EXPRESSION_START);
                $this->stream->expect(Token::ARGUMENT_LIST_START, 'array');
                $this->stream->expect(Token::STRING);
                break;
            case 'set':
            case 'not set':
                $this->stream->expect(Token::IDENTIFIER)
                        ->then(Token::ARGUMENT_LIST_START, 'args', 1, true)
                        ->also(Token::ARGUMENT_LIST_START, 'array', 1, true);
                break;
            case 'using':
                $this->stream->expect(Token::STRING);
                break;
        }
    }

    private function parseLiteralOrIdentifier($type, $token)
    {
        switch ($this->getState()) {
            case self::STATE_POSSIBLE_FLOAT:
                $this->popState();
                if ($type == Token::LITERAL) {
                    if (is_numeric($token) && $token > 0) {
                        $this->stream->pop();
                        $integer_part = $this->stream->pop()->getValue();
                        $token        = $integer_part . '.' . $token;
                    }
                }
                break;
            case self::STATE_ARRAY:
            case self::STATE_ARGUMENT_LIST:
                break;
        }
        $this->pushToken($type, $token);
    }

    //Utilities
    public function throwException($type)
    {
        $args   = func_get_args();
        array_shift($args);
        $args[] = $this->line;
        throw new SyntaxException(vsprintf(self::$exceptions[$type], $args));
    }

    public function pushToken($type, $value = null)
    {
        $this->stream->push(new Token($type, $value, $this->line));
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
            'value' => $value,
            'line'  => $this->line
        );
    }

    public function popState()
    {
        if (empty($this->state_stack)) {
            throw new SyntaxException('Unexpected closing tag in line ' . $this->line);
        }
        array_pop($this->state_stack);
    }

    public function isState($checked, $value = null, $pop = false)
    {
        if (is_array($checked)) {
            $args    = $checked;
            $checked = array_shift($args);
            $value   = array_shift($args);
        }
        if ($checked !== $this->getState()) {
            return false;
        }
        if ($value !== null && $value !== $this->getStateValue()) {
            return false;
        }
        if ($pop) {
            $this->popState();
        }
        return true;
    }

    public function getTokenStream()
    {
        return $this->stream;
    }

    private function getStateString()
    {
        $state = $this->getState();
        if (isset(self::$states[$state])) {
            return self::$states[$state];
        }
        return 'unknown (' . $state . ')';
    }
}
