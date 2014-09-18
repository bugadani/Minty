<?php

/**
 * This file is part of the Minty templating library.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Minty\Compiler\Tags;

use Minty\Compiler\Nodes\ArrayNode;
use Minty\Compiler\Nodes\DataNode;
use Minty\Compiler\Nodes\ExpressionNode;
use Minty\Compiler\Nodes\RootNode;
use Minty\Compiler\Nodes\TagNode;
use Minty\Compiler\Nodes\TempVariableNode;
use Minty\Compiler\Parser;
use Minty\Compiler\Stream;
use Minty\Compiler\Tag;
use Minty\Compiler\Tags\Helpers\MethodNodeHelper;
use Minty\Compiler\Token;

class ListTag extends Tag
{
    /**
     * @var MethodNodeHelper
     */
    private $helper;

    public function __construct(MethodNodeHelper $helper)
    {
        $this->helper = $helper;
    }

    public function getTag()
    {
        return 'list';
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $environment = $parser->getEnvironment();

        $node = new TagNode(
            $environment->getTag('for'), [
                'save_temp_var' => true,
                'create_stack'  => true,
                'variables'     => 1
            ]
        );

        $node->addChild($parser->parseExpression($stream), 'source');

        $temp     = $node->addChild(new TempVariableNode('element'), 'loop_variable_0');
        $loopBody = $node->addChild(new RootNode(), 'loop_body');

        if ($stream->current()->test(Token::IDENTIFIER, 'as')) {

            $arrayNode = new ArrayNode();
            $arrayNode->add(
                $temp,
                new DataNode($stream->expect(Token::VARIABLE)->getValue())
            );

            $setOperator = $parser->getEnvironment()->getBinaryOperators()->getOperator(':');
            $varNode     = $setOperator->createNode($temp, $arrayNode);

            $loopBody->addChild(new ExpressionNode($varNode));

            $stream->next();
        }
        $stream->expectCurrent(Token::IDENTIFIER, 'using');

        $loopBody->addChild(
            $this->helper->createRenderFunctionNode(
                $parser->parseExpression($stream),
                $temp
            )
        );

        return $node;
    }
}
