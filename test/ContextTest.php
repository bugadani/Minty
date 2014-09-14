<?php

namespace Minty;

class ContextTest extends \PHPUnit_Framework_TestCase
{
    public function testStrictModeOff()
    {
        $context = new Context(false);

        $this->assertEquals('foobar', $context->foobar);
    }
}
