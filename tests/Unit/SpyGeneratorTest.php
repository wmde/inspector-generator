<?php

declare(strict_types=1);

namespace Wmde\SpyGenerator\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wmde\SpyGenerator\SpyGenerator;
use Wmde\SpyGenerator\Tests\Classes\Order;

/**
 * @covers \Wmde\SpyGenerator\SpyGenerator
 */
class SpyGeneratorTest extends TestCase
{
	public function test_it_generates_class(): void
	{
		$generator = new SpyGenerator('Wmde\SpyGenerator\Tests\Generated');

		$code = $generator->generateSpy(Order::class, 'OrderSpy');

		$fileName = __DIR__ . "/../Generated/OrderSpy.php";
		file_put_contents($fileName, "<?php\ndeclare(strict_types=1);\n\n$code");
		require $fileName;

		$this->assertTrue(class_exists('Wmde\SpyGenerator\Tests\Generated\OrderSpy', true));
	}


	public function test_generated_class_provides_access_to_properties(): void
    {
		$spyClass = new \Wmde\SpyGenerator\Tests\Generated\OrderSpy($this->newOrderFixture());

		$this->assertTrue($spyClass->getFulfilled());
		$this->assertSame(1000, $spyClass->getAmount());
		$this->assertSame('Fulfilled by Joe', $spyClass->getComment());
		$this->assertSame(0.2, $spyClass->getRebate());
	}

	private function newOrderFixture(): Order
    {
		$order = new Order('1');
		$order->addItem('Test item', 1000);
		$order->applyRebate(0.2);
		$order->fulfill('Fulfilled by Joe');
		return $order;
	}
}
