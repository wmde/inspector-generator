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
	public function test_it_provides_access_to_boolean_properties(): void
    {
		$generator = new SpyGenerator('Wmde\SpyGenerator\Tests\Generated');

		$spyClassCode = $generator->generateSpy(Order::class, 'BooleanOrderSpy');
		file_put_contents(
			__DIR__ . '/../Generated/BooleanOrderSpy.php',
			"<?php\ndeclare(strict_types=1);\n\n" . $spyClassCode
		);
		require_once __DIR__ . '/../Generated/BooleanOrderSpy.php';
		$spyClass = new \Wmde\SpyGenerator\Tests\Generated\BooleanOrderSpy($this->newOrderFixture());

		$this->assertTrue($spyClass->getFulfilled());
	}

	public function test_it_provides_access_to_integer_properties(): void
    {
		$generator = new SpyGenerator('Wmde\SpyGenerator\Tests\Generated');

		$spyClassCode = $generator->generateSpy(Order::class, 'IntegerOrderSpy');
		file_put_contents(
			__DIR__ . '/../Generated/IntegerOrderSpy.php',
			"<?php\ndeclare(strict_types=1);\n\n" . $spyClassCode
		);
		require_once __DIR__ . '/../Generated/IntegerOrderSpy.php';
		$spyClass = new \Wmde\SpyGenerator\Tests\Generated\IntegerOrderSpy($this->newOrderFixture());

		$this->assertSame(1000, $spyClass->getAmount());
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
