<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler;

use Closure;
use Modules\Templating\Compiler\Exceptions\ParseException;
use Modules\Templating\Compiler\Exceptions\SyntaxException;
use Modules\Templating\Compiler\Nodes\RootNode;
use Modules\Templating\Compiler\Nodes\TextNode;
use Modules\Templating\Environment;

class Parser
{
    /**
     * @var Tag[]
     */
    private $tags;

    /**
     * @var ExpressionParser
     */
    private $expressionParser;

    public function __construct(Environment $environment)
    {
        $this->expressionParser = new ExpressionParser($environment);
        $this->tags             = $environment->getTags();
    }

    public function parseToken(Stream $stream)
    {
        $token = $stream->current();
        $value = $token->getValue();

        switch ($token->getType()) {
            case Token::TEXT:
                return new TextNode($value);

            case Token::BLOCK_START:
            case Token::TAG:
                if (!isset($this->tags[$value])) {
                    $line = $token->getLine();
                    throw new ParseException("Unknown {$value} tag found in line {$line}");
                }
                $stream->next();

                return $this->tags[$value]->parse($this, $stream);

            default:
                $type = $token->getTypeString();
                $line = $token->getLine();
                throw new SyntaxException("Unexpected {$type} ({$value}) token found in line {$line}");
        }
    }

    public function parse(Stream $stream, Closure $endCondition = null)
    {
        $root = new RootNode();
        if ($endCondition) {
            while (!$endCondition($stream)) {
                $node = $this->parseToken($stream);
                $node->setParent($root);
            }
        } else {
            while (!$stream->next()->test(Token::EOF)) {
                $node = $this->parseToken($stream);
                $node->setParent($root);
            }
        }

        return $root;
    }

    /**
     * @param Stream $stream
     *
     * @return Node
     */
    public function parseExpression(Stream $stream)
    {
        return $this->expressionParser->parse($stream);
    }
}
