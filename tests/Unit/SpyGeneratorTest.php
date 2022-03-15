<?php

declare(strict_types=1);

namespace Wmde\SpyGenerator\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wmde\SpyGenerator\CodeWriter;
use Wmde\SpyGenerator\Psr4CodeWriter;
use Wmde\SpyGenerator\SpyGenerator;
use Wmde\SpyGenerator\Tests\Classes\Order;
use Wmde\SpyGenerator\Tests\Classes\SpecialOrder;

/**
 * @covers \Wmde\SpyGenerator\SpyGenerator
 */
class SpyGeneratorTest extends TestCase
{
	public function test_it_generates_class(): void
	{
		$generator = new SpyGenerator('Wmde\SpyGenerator\Tests\Generated');
		$writer = $this->makeWriter();
		$result = $generator->generateSpy(Order::class, 'OrderSpy');

		$fileName = $writer->writeResult($result);
		/**
		 * @psalm-suppress UnresolvableInclude
		 */
		require $fileName;

		$this->assertSame('Wmde\SpyGenerator\Tests\Generated\OrderSpy', $result->fullyQualifiedClassName);
		$this->assertTrue(class_exists('\Wmde\SpyGenerator\Tests\Generated\OrderSpy', false));
	}


	/**
	 * @depends test_it_generates_class
	 */
	public function test_generated_class_provides_access_to_properties(): void
    {
		$spyClass = new \Wmde\SpyGenerator\Tests\Generated\OrderSpy($this->newOrderFixture());

		$this->assertTrue($spyClass->getFulfilled());
		$this->assertSame(1000, $spyClass->getAmount());
		$this->assertSame('Fulfilled by Joe', $spyClass->getComment());
		$this->assertSame(0.2, $spyClass->getRebate());
		$this->assertSame(['Test item'], $spyClass->getItems());
		$this->assertNotNull($spyClass->getPrevious());
	}

	/**
	 * @depends test_it_generates_class
	 */
	public function test_generated_class_provides_access_to_nullable_properties(): void
    {
		$spyClass = new \Wmde\SpyGenerator\Tests\Generated\OrderSpy($this->newSingularOrderFixture());

		$this->assertNull($spyClass->getPrevious());
	}

	public function test_generated_class_provides_access_to_inherited_properties(): void
    {
		$generator = new SpyGenerator('Wmde\SpyGenerator\Tests\Generated');
		$result = $generator->generateSpy(SpecialOrder::class, 'SpecialOrderSpy');
		$writer = $this->makeWriter();
		$fileName = $writer->writeResult($result);
		/**
		 * @psalm-suppress UnresolvableInclude
		 */
		require $fileName;

		$spyClass = new \Wmde\SpyGenerator\Tests\Generated\SpecialOrderSpy($this->newSpecialOrderFixture());

		$this->assertSame('Lots and lots of love', $spyClass->getSpecialSauce());
		$this->assertSame('99', $spyClass->getId());
	}

	private function newOrderFixture(): Order
    {
		$order = new Order('2');
		$order->addItem('Test item', 1000);
		$order->applyRebate(0.2);
		$order->fulfill('Fulfilled by Joe', new Order('1'));
		return $order;
	}

	private function newSingularOrderFixture(): Order
    {
		return new Order('2');
	}

	private function newSpecialOrderFixture(): SpecialOrder
	{
		$order = new SpecialOrder('99');
		$order->addLove();
		return $order;
	}

	private function makeWriter(): CodeWriter
    {
		return new Psr4CodeWriter([
			'Wmde\SpyGenerator\Tests\\' => __DIR__ . '/../'
		]);
	}
}
