<?php

/**
 * This file is part of the Miny framework.
 * (c) Dániel Buga <daniel@bugadani.hu>
 *
 * For licensing information see the LICENSE file.
 */

namespace Modules\Templating\Compiler\Tags\TemplateExtension;

use Modules\Templating\Compiler\Compiler;
use Modules\Templating\Compiler\Nodes\TagNode;
use Modules\Templating\Compiler\Parser;
use Modules\Templating\Compiler\Stream;
use Modules\Templating\Compiler\Tag;
use Modules\Templating\Compiler\Token;

class EmbedTag extends Tag
{

    public function hasEndingTag()
    {
        return true;
    }

    public function getTag()
    {
        return 'embed';
    }

    public function compile(Compiler $compiler, array $data)
    {
        $embedded = $compiler->addEmbedded($data['template'], $data['body']);

        $compiler
            ->indented(
                '$embedded = new %s($this->getLoader(), $this->getEnvironment());',
                $embedded
            )
            ->indented('$embedded->set(')
            ->compileData($data['arguments'])
            ->add(');');

        $compiler->indented('$embedded->render();');
    }

    public function parse(Parser $parser, Stream $stream)
    {
        $node = new TagNode($this, array(
            'template'  => $stream->expect(Token::STRING)->getValue()
        ));

        if ($stream->nextTokenIf(Token::IDENTIFIER, 'using')) {
            $arguments = $parser->parseExpression($stream);
        } else {
            $arguments = array();
        }

        $bodyNode = $parser->parse(
            $stream,
            function (Stream $stream) {
                return $stream->next()->test(Token::TAG, 'endembed');
            }
        );
        $bodyNode->setParent($node);

        $node->addData('arguments', $arguments);
        $node->addData('body', $bodyNode);

        return $node;
    }
}
