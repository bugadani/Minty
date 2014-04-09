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
    private $expression_parser;

    public function __construct(Environment $environment)
    {
        $this->expression_parser = new ExpressionParser($environment);
        $this->tags              = $environment->getTags();
    }

    public function parseToken(Stream $stream)
    {
        $token = $stream->current();
        switch ($token->getType()) {
            case Token::TEXT:
                return new TextNode($token->getValue());

            case Token::BLOCK_START:
            case Token::TAG:
                $tag = $token->getValue();
                if (!isset($this->tags[$tag])) {
                    $message = sprintf('Unknown %s tag found in line %s', $tag, $token->getLine());
                    throw new ParseException($message);
                }
                $stream->next();
                $parser = $this->tags[$tag];

                return $parser->parse($this, $stream);

            case Token::EXPRESSION_START:
                $parser = $this->tags['output'];

                return $parser->parse($this, $stream);

            default:
                $pattern = 'Unexpected %s (%s) token found in line %d';
                $exception = sprintf(
                    $pattern,
                    $token->getTypeString(),
                    $token->getValue(),
                    $token->getLine()
                );
                throw new SyntaxException($exception);
        }
    }

    public function parse(Stream $stream, Closure $end_condition = null)
    {
        $root = new RootNode();
        if ($end_condition === null) {
            $end_condition = function (Stream $stream) {
                return $stream->next()->test(Token::EOF);
            };
        }
        while (!$end_condition($stream)) {
            $node = $this->parseToken($stream);
            $root->addChild($node);
        }

        return $root;
    }

    public function parseExpression(Stream $stream)
    {
        return $this->expression_parser->parse($stream);
    }
}
