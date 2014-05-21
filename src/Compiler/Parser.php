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

    private function parseToken(Stream $stream, RootNode $root)
    {
        $token = $stream->current();
        $value = $token->getValue();

        switch ($token->getType()) {
            case Token::TEXT:
                $node = new TextNode($value);
                break;

            case Token::TAG:
                if (!isset($this->tags[$value])) {
                    $line = $token->getLine();
                    throw new ParseException("Unknown {$value} tag", $line);
                }
                $stream->next();

                $node = $this->tags[$value]->parse($this, $stream);
                break;

            default:
                $type = $token->getTypeString();
                $line = $token->getLine();
                throw new SyntaxException("Unexpected {$type} ({$value}) token" ,$line);
        }
        $node->setParent($root);
        return $stream->next();
    }

    public function parse(Stream $stream, Closure $endCondition = null)
    {
        $root = new RootNode();
        $token = $stream->next();

        if ($endCondition) {
            while (!$endCondition($token)) {
                $token = $this->parseToken($stream, $root);
            }
        } else {
            while (!$token->test(Token::EOF)) {
                $token = $this->parseToken($stream, $root);
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
