<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler;

use Minty\Compiler\Exceptions\ParseException;
use Minty\Compiler\Nodes\ClassNode;
use Minty\Compiler\Nodes\FileNode;
use Minty\Compiler\Nodes\PrintNode;
use Minty\Compiler\Nodes\RootNode;
use Minty\Environment;

class Parser
{
    /**
     * @var ExpressionParser
     */
    private $expressionParser;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var FileNode
     */
    private $fileNode;

    /**
     * @var ClassNode
     */
    private $classNode;

    private $level = 0;
    private $block;
    private $blocks = [];

    public function __construct(Environment $environment, ExpressionParser $expressionParser)
    {
        $this->expressionParser = $expressionParser;
        $this->environment      = $environment;
    }

    /**
     * @return Environment
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    private function parseToken(Token $token, Stream $stream, RootNode $root)
    {
        $value = $token->getValue();

        switch ($token->getType()) {
            case Token::TEXT:
                $root->addChild(
                    new PrintNode($value)
                );
                break;

            case Token::TAG_START:
                try {
                    $node = $this->environment->getTag($value)->parse($this, $stream);
                    if ($node instanceof Node) {
                        $node->addData('line', $token->getLine());
                        $root->addChild($node);
                    }
                } catch (\OutOfBoundsException $e) {
                    throw new ParseException("Unknown {$value} tag", $token->getLine(), $e);
                }

                break;

            default:
                $type = $token->getTypeString();
                $line = $token->getLine();
                throw new ParseException("Unexpected {$type} ({$value}) token", $line);
        }

        return $stream->next();
    }

    public function inMainScope()
    {
        return $this->level === 1;
    }

    public function parseTemplate(Stream $stream, $templateName)
    {
        $fileNode = new FileNode($this->environment);

        $this->fileNode  = $fileNode;
        $this->classNode = $fileNode->addClass($templateName);
        $this->classNode->addChild(
            $this->parseBlock($stream, null, Token::EOF),
            ClassNode::MAIN_TEMPLATE_BLOCK
        );

        return $fileNode;
    }

    public function getCurrentClassNode()
    {
        return $this->classNode;
    }

    public function setCurrentClassNode(ClassNode $classNode)
    {
        $this->classNode = $classNode;
    }

    public function getCurrentFileNode()
    {
        return $this->fileNode;
    }

    public function parseBlock(Stream $stream, $endTags, $type = Token::TAG_START)
    {
        ++$this->level;
        $root  = new RootNode();
        $token = $stream->next();

        while (!$token->test($type, $endTags)) {
            $token = $this->parseToken($token, $stream, $root);
        }

        --$this->level;

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

    public function enterBlock($blockName)
    {
        $this->blocks[] = $this->block;
        $this->block    = $blockName;
    }

    public function leaveBlock()
    {
        $this->block = array_pop($this->blocks);
    }

    public function getCurrentBlock()
    {
        if (!isset($this->block)) {
            throw new ParseException('Currently not in a block.');
        }

        return $this->block;
    }
}
