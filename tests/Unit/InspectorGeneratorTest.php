<?php

declare(strict_types=1);

namespace WMDE\InspectorGenerator\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use WMDE\InspectorGenerator\Psr4CodeWriter;
use WMDE\InspectorGenerator\GeneratedInspectorResult;
use WMDE\InspectorGenerator\InspectorGenerator;
use WMDE\InspectorGenerator\Tests\ExampleClasses\NullableOrder;
use WMDE\InspectorGenerator\Tests\ExampleClasses\Order;
use WMDE\InspectorGenerator\Tests\ExampleClasses\SpecialOrder;

/**
 * @covers \WMDE\InspectorGenerator\InspectorGenerator
 */
class InspectorGeneratorTest extends TestCase
{
	public function test_it_generates_class(): void
	{
		$generator = new InspectorGenerator('WMDE\InspectorGenerator\Tests\Generated');

		$result = $generator->generateInspector(Order::class, 'OrderInspector');
		$this->loadClassCode($result);

		$this->assertSame('WMDE\InspectorGenerator\Tests\Generated\OrderInspector', $result->fullyQualifiedClassName);
		$this->assertTrue(class_exists('\WMDE\InspectorGenerator\Tests\Generated\OrderInspector', false));
	}


	/**
	 * @depends test_it_generates_class
	 */
	public function test_generated_class_provides_access_to_properties(): void
    {
		$inspectorClass = new \WMDE\InspectorGenerator\Tests\Generated\OrderInspector($this->newOrderFixture());

		$this->assertTrue($inspectorClass->getFulfilled());
		$this->assertSame(1000, $inspectorClass->getAmount());
		$this->assertSame('Fulfilled by Joe', $inspectorClass->getComment());
		$this->assertSame(0.2, $inspectorClass->getRebate());
		$this->assertSame(['Test item'], $inspectorClass->getItems());
		$this->assertSame('O-', $inspectorClass->getPrefix());
		$this->assertNotNull($inspectorClass->getPrevious());
	}

	public function test_generated_class_provides_access_to_nullable_properties(): void
	{
		$generator = new InspectorGenerator('WMDE\InspectorGenerator\Tests\Generated');
		$result = $generator->generateInspector(NullableOrder::class, 'NullableOrderInspector');
		$this->loadClassCode($result);

		$inspectorClass = new \WMDE\InspectorGenerator\Tests\Generated\NullableOrderInspector(
			$this->newNullableOrderFixture()
		);

		$this->assertNull($inspectorClass->getFulfilled());
		$this->assertNull($inspectorClass->getAmount());
		$this->assertNull($inspectorClass->getComment());
		$this->assertNull($inspectorClass->getRebate());
		$this->assertNull($inspectorClass->getPrevious());
	}

	public function test_generated_class_provides_access_to_inherited_properties(): void
    {
		$generator = new InspectorGenerator('WMDE\InspectorGenerator\Tests\Generated');
		$result = $generator->generateInspector(SpecialOrder::class, 'SpecialOrderInspector');
		$this->loadClassCode($result);

		$inspectorClass = new \WMDE\InspectorGenerator\Tests\Generated\SpecialOrderInspector(
			$this->newSpecialOrderFixture()
		);

		$this->assertSame('Lots and lots of love', $inspectorClass->getSpecialSauce());
		$this->assertSame('99', $inspectorClass->getId());
		$reflectedInspector = new ReflectionClass($inspectorClass);
		$this->assertFalse(
			$reflectedInspector->hasMethod('getComment'),
			'Inspector class must not contain accessors for private of parent class'
		);
	}

	private function newOrderFixture(): Order
    {
		$order = new Order('2');
		$order->addItem('Test item', 1000);
		$order->applyRebate(0.2);
		$order->fulfill('Fulfilled by Joe', new Order('1'));
		return $order;
	}

	private function newNullableOrderFixture(): NullableOrder
    {
		return new NullableOrder('2');
	}

	private function newSpecialOrderFixture(): SpecialOrder
	{
		$order = new SpecialOrder('99');
		$order->addLove();
		return $order;
	}

	private function loadClassCode(GeneratedInspectorResult $result): void
    {
        $writer = new Psr4CodeWriter([
			'WMDE\InspectorGenerator\Tests\\' => __DIR__ . '/../'
		]);
		$fileName = $writer->writeResult($result);
		/**
		 * @psalm-suppress UnresolvableInclude
		 */
		require $fileName;
	}
}
