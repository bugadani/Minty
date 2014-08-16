<?php

namespace Minty\Compiler;

class OperatorCollectionTest extends \PHPUnit_Framework_TestCase
{
    private $mockOperator;

    /**
     * @var OperatorCollection
     */
    private $collection;
    private $otherOperator;

    public function setUp()
    {
        $this->mockOperator = $this->getMockBuilder('\\Minty\\Compiler\\Operator')
            ->setMethods(['operators'])
            ->setConstructorArgs([1])
            ->getMockForAbstractClass();

        $this->mockOperator->expects($this->any())
            ->method('operators')
            ->will($this->returnValue(['+', '-']));

        $this->otherOperator = $this->getMockBuilder('\\Minty\\Compiler\\Operator')
            ->setMethods(['operators'])
            ->setConstructorArgs([1])
            ->getMockForAbstractClass();

        $this->otherOperator->expects($this->any())
            ->method('operators')
            ->will($this->returnValue('*'));

        $this->collection = new OperatorCollection();

        $this->collection->addOperator($this->mockOperator);
        $this->collection->addOperator($this->otherOperator);
    }

    public function testEmptyCollection()
    {
        $collection = new OperatorCollection();

        $this->assertFalse($collection->isOperator('something'));
        $this->assertEmpty($collection->getSymbols());
    }

    public function testAllSymbolsAreAdded()
    {
        $this->assertEquals(['+', '-', '*'], $this->collection->getSymbols());

        $this->assertTrue($this->collection->isOperator('+'));
        $this->assertTrue($this->collection->isOperator('-'));
        $this->assertTrue($this->collection->isOperator('*'));
    }

    public function testOperatorsAreReturnedBySymbol()
    {
        $this->assertTrue($this->collection->exists($this->mockOperator));
        $this->assertTrue($this->collection->exists($this->otherOperator));
        $this->assertFalse(
            $this->collection->exists(
                $this->getMockBuilder('\\Minty\\Compiler\\Operator')
                    ->disableOriginalConstructor()
                    ->getMockForAbstractClass()
            )
        );

        $this->assertSame($this->mockOperator, $this->collection->getOperator('+'));
        $this->assertSame($this->mockOperator, $this->collection->getOperator('-'));
        $this->assertSame($this->otherOperator, $this->collection->getOperator('*'));
    }

}
