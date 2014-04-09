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

                return $this->tags[$tag]->parse($this, $stream);

            case Token::EXPRESSION_START:
                return $this->tags['output']->parse($this, $stream);

            default:
                throw new SyntaxException(sprintf(
                    'Unexpected %s (%s) token found in line %d',
                    $token->getTypeString(),
                    $token->getValue(),
                    $token->getLine()
                ));
        }
    }

    public function parse(Stream $stream, Closure $end_condition = null)
    {
        $end_condition = $end_condition ? : function (Stream $stream) {
            return $stream->next()->test(Token::EOF);
        };

        $root = new RootNode();
        while (!$end_condition($stream)) {
            $root->addChild($this->parseToken($stream));
        }

        return $root;
    }

    public function parseExpression(Stream $stream)
    {
        return $this->expressionParser->parse($stream);
    }
}
