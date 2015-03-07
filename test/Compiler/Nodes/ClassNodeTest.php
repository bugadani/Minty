<?php

namespace Minty\Compiler;

use Minty\Compiler\Nodes\ClassNode;
use Minty\Environment;
use Minty\TemplateLoaders\FileLoader;

class ClassNodeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ClassNode
     */
    private $node;

    public function setUp()
    {
        $env = new Environment(new FileLoader('.', ''), [
            'cache_namespace' => 'Test'
        ]);

        $this->node = new ClassNode($env, 'test');
    }

    public function testGetNameSpace()
    {
        $this->assertEquals('Test', $this->node->getNameSpace());
    }
}
