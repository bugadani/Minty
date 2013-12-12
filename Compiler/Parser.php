<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler;

use Modules\Templating\Compiler\Exceptions\SyntaxException;
use Modules\Templating\Compiler\Nodes\RootNode;
use Modules\Templating\Compiler\Nodes\TextNode;

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
                    throw new Exceptions\ParseException('Tag not found: ' . $tag);
                } else {
                    $stream->next();
                }
                $parser = $this->tags[$tag];
                return $parser->parse($this, $stream);

            case Token::EXPRESSION_START:
                $parser = $this->tags['output'];
                return $parser->parse($this, $stream);

            default:
                $pattern   = 'Unexpected %s (%s) token found in line %d';
                $exception = sprintf($pattern, $token->getTypeString(), $token->getValue(), $token->getLine());
                throw new SyntaxException($exception);
        }
    }

    public function parse(Stream $stream, $end_type = Token::EOF, $end_value = null)
    {
        $root = new RootNode();
        while (!$stream->next()->test($end_type, $end_value)) {
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
