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

    public function parseToken(Stream $stream, $tag_parser = null)
    {
        $token = $stream->current();
        switch ($token->getType()) {
            case Token::TEXT:
                return new TextNode($token->getValue());

            case Token::BLOCK_START:
            case Token::TAG:
                $tag = $token->getValue();
                if (!isset($this->tags[$tag])) {
                    if (is_callable($tag_parser)) {
                        $return = call_user_func($tag_parser, $stream);
                        if ($return) {
                            return;
                        }
                    }
                } else {
                    $stream->next();
                }
                $parser = $this->tags[$tag];
                return $parser->parse($this, $stream);

            case Token::EXPRESSION_START:
                if (is_callable($tag_parser)) {
                    $return = call_user_func($tag_parser, $stream);
                    if ($return) {
                        return;
                    }
                }

                $parser = $this->tags['output'];
                return $parser->parse($this, $stream);

            default:
                $exception = sprintf('Unexpected %s (%s) token found in line %d', $token->getTypeString(),
                        $token->getValue(), $token->getLine());
                throw new SyntaxException($exception);
        }
    }

    public function parse(Stream $stream)
    {
        $root = new RootNode();
        $next = $stream->next();
        while (!$next->test(Token::EOF)) {
            $node = $this->parseToken($stream);
            $root->addChild($node);
            $next = $stream->next();
        }
        return $root;
    }

    public function parseExpression(Stream $stream)
    {
        return $this->expression_parser->parse($stream);
    }
}
