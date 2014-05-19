<?php

namespace Modules\Templating\Compiler;

class OperatorCollectionTest extends \PHPUnit_Framework_TestCase
{
    public function testEmptyCollection()
    {
        $collection = new OperatorCollection();

        $this->assertFalse($collection->isOperator('something'));
        $this->assertEmpty($collection->getSymbols());
    }
}
