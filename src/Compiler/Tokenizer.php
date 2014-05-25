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

    private $expressionPartsPattern;
    private $punctuation;
    private $line;
    private $fallbackTagName;
    private $blockEndPrefix;
    private $blockEndingTags;
    private $parts;
    private $position;

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

        $this->blockEndingTags = array();
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

        $this->expressionPartsPattern = $this->getExpressionPartsPattern(
            array(
                'true',
                'false',
                'null',
                '$[a-zA-Z]+[a-zA-Z_\-0-9]*',
                ':[a-zA-Z]+[a-zA-Z_\-0-9]*',
                '(?<!\w)\d+(?:\.\d+)?',
                '"(?:\\\\.|[^"\\\\])*"',
                "'(?:\\\\.|[^'\\\\])*'"
            )
        );
    }

    private function getExpressionPartsPattern(array $dataPatterns)
    {
        $patterns = array();
        $signs    = ' ';

        foreach (array_merge($this->operators, $this->punctuation) as $symbol) {
            $length = strlen($symbol);
            if ($length == 1) {
                $signs .= $symbol;
            } else {
                $quotedSymbol = preg_quote($symbol, '/');
                if (preg_match('/^[a-zA-Z\ ]+$/', $symbol)) {
                    $quotedSymbol = "(?<=^|\\W){$quotedSymbol}(?=[\\s()\\[\\]]|$)";
                }
                $patterns[$quotedSymbol] = $length;
            }
        }
        foreach($dataPatterns as $pattern) {
            $patterns[$pattern] = strlen($pattern);
        }
        arsort($patterns);

        $patterns = implode('|', array_keys($patterns));
        $signs    = preg_quote($signs, '/');

        return "/({$patterns}|[{$signs}])/i";
    }

    public function tokenize($template)
    {
        $this->line   = 1;
        $this->tokens = new Stream();

        $pattern_parts = array();
        foreach ($this->delimiters as $delimiters) {
            foreach ($delimiters as $delimiter) {
                $pattern                 = preg_quote($delimiter, '/');
                $pattern_parts[$pattern] = strlen($pattern);
            }
        }
        arsort($pattern_parts);
        $delimiterPatterns = implode('|', array_keys($pattern_parts));

        $flags = PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY;

        $this->parts    = preg_split("/({$delimiterPatterns}|[\"'])/", $template, -1, $flags);
        $this->position = -1;

        $currentText = '';
        while (isset($this->parts[++$this->position])) {
            switch ($this->parts[$this->position]) {
                case $this->delimiters['comment'][0]:
                    $this->pushToken(Token::TEXT, $currentText);
                    $currentText = '';
                    $this->tokenizeComment();
                    break;

                case $this->delimiters['tag'][0]:
                    $this->pushToken(Token::TEXT, $currentText);
                    $currentText = '';
                    $this->tokenizeTag();
                    break;

                default:
                    $currentText .= $this->parts[$this->position];
                    break;
            }
        }

        $this->pushToken(Token::TEXT, $currentText);
        $this->pushToken(Token::EOF);

        $this->tokens->rewind();

        return $this->tokens;
    }

    private function tokenizeComment()
    {
        while (isset($this->parts[++$this->position])) {
            if ($this->parts[$this->position] === $this->delimiters['comment'][1]) {
                break;
            }
            $this->line += substr_count($this->parts[$this->position], "\n");
        }
        if (!isset($this->parts[$this->position])) {
            throw new SyntaxException('Unterminated comment', $this->line);
        }
    }

    private function tokenizeTag()
    {
        $tagExpression = '';
        while (isset($this->parts[++$this->position])) {
            switch ($this->parts[$this->position]) {
                case $this->delimiters['tag'][1]:
                    if (!$this->processTag(trim($tagExpression))) {
                        $this->tokenizeRawBlock();
                    }
                    break 2;

                case '"':
                case "'":
                    $tagExpression .= $this->tokenizeString($this->parts[$this->position]);
                    break;

                default:
                    $tagExpression .= $this->parts[$this->position];
                    break;
            }
        }
        if (!isset($this->parts[$this->position])) {
            throw new SyntaxException('Unterminated tag', $this->line);
        }
    }

    private function tokenizeRawBlock()
    {
        $text   = '';
        $endRaw = $this->blockEndPrefix . 'raw';
        while (isset($this->parts[++$this->position])) {
            $p = $this->position;
            if ($this->parts[$p] === $this->delimiters['tag'][0]) {
                if (isset($this->parts[++$p]) && trim($this->parts[$p]) === $endRaw) {
                    if (isset($this->parts[++$p]) && $this->parts[$p] === $this->delimiters['tag'][1]) {
                        $this->position += 2;
                        break;
                    }
                }
            }
            $text .= $this->parts[$this->position];
        }

        $this->pushToken(Token::TEXT, $text);

        if (!isset($this->parts[$this->position])) {
            throw new SyntaxException('Unterminated raw block', $this->line);
        }
    }

    private function tokenizeString($delimiter)
    {
        $string = $delimiter;
        while (isset($this->parts[++$this->position])) {
            $part = $this->parts[$this->position];
            if ($part === $delimiter) {
                $in_string = false;
                //Let's walk from the previous character backwards
                $i = strlen($string);
                while ($i > 0 && $string[--$i] === '\\') {
                    //If we find one backslash, we flip back the flag to true
                    //2 backslashes, flag is false... even = the string has ended
                    $in_string = !$in_string;
                }
                if (!$in_string) {
                    break;
                }
            }
            $string .= $part;

        }
        $string .= $delimiter;
        if (!isset($this->parts[$this->position])) {
            throw new SyntaxException('Unterminated string', $this->line);
        }

        return $string;
    }

    private function processTag($tag)
    {
        if (isset($this->blockEndingTags[$tag])) {
            $this->pushToken(Token::TAG, $this->blockEndingTags[$tag]);

            return true;
        }

        foreach ($this->patternBasedTags as $tagName => $unnamedTag) {
            if ($unnamedTag->matches($tag)) {
                $this->pushToken(Token::TAG, $tagName);
                $unnamedTag->tokenize($this, $tag);

                return true;
            }
        }

        $parts = preg_split("/([ (\n\t])/", $tag, 2, PREG_SPLIT_DELIM_CAPTURE);

        $tagName = $parts[0];
        if ($tagName === 'raw') {
            return false;
        }

        if (count($parts) === 3) {
            $expression = $parts[1] . $parts[2];
        } else {
            $expression = null;
        }

        if (!isset($this->tags[$tagName])) {
            $tagName    = $this->fallbackTagName;
            $expression = $tag;

            if (!isset($this->tags[$tagName])) {
                throw new ParseException("Unknown tag \"{$tagName}\"", $this->line);
            }
        }
        $tag = $this->tags[$tagName];
        $tag->addNameToken($this);
        $tag->tokenize($this, $expression);

        return true;
    }

    private function tokenizeExpressionPart($part)
    {
        if (in_array($part, $this->punctuation)) {
            $this->pushToken(Token::PUNCTUATION, $part);
        } elseif (in_array($part, $this->operators)) {
            $this->pushToken(Token::OPERATOR, $part);
        } elseif (is_numeric($part)) {
            $this->pushToken(Token::LITERAL, $part);
        } else {
            switch ($part[0]) {
                case '"':
                case "'":
                    //strip backslashes from double-slashes and escaped string delimiters
                    $stripSlashes = array(
                        "\\{$part[0]}" => $part[0],
                        '\\\\'         => '\\'
                    );
                    $part         = strtr($part, $stripSlashes);
                    $this->pushToken(Token::STRING, substr($part, 1, -1));
                    break;

                case ':':
                    $this->pushToken(Token::STRING, ltrim($part, ':'));
                    break;

                default:
                    switch (strtolower($part)) {
                        case 'null':
                            $this->pushToken(Token::LITERAL, null);
                            break;
                        case 'true':
                            $this->pushToken(Token::LITERAL, true);
                            break;
                        case 'false':
                            $this->pushToken(Token::LITERAL, false);
                            break;
                        default:
                            if (trim($part) !== '') {
                                $this->pushToken(Token::IDENTIFIER, $part);
                            }
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
        foreach (preg_split($this->expressionPartsPattern, $expr, null, $flags) as $part) {
            if($part !== ' ') {
                $this->tokenizeExpressionPart($part);
                $this->line += substr_count($part, "\n");
            }
        }
    }

    public function pushToken($type, $value = null)
    {
        if ($type === Token::TEXT) {
            if ($value === '') {
                return;
            }
            $this->tokens->push(new Token($type, $value, $this->line));
            $this->line += substr_count($value, "\n");
        } else {
            $this->tokens->push(new Token($type, $value, $this->line));
        }
    }
}
