<?php
declare(strict_types=1);

namespace Wmde\SpyGenerator\Unit;

use PHPUnit\Framework\TestCase;
use Wmde\SpyGenerator\SpyGenerator;
use Wmde\SpyGenerator\Tests\Classes\Order;

/**
 * @covers \Wmde\SpyGenerator\SpyGenerator
 */
class SpyGeneratorTest extends TestCase {

	public function test_it_provides_access_to_boolean_properties(): void {
		$generator = new SpyGenerator('Wmde\SpyGenerator\Test\Generated');

		$spyClassCode = $generator->generateSpy( Order::class, 'BooleanOrderSpy');
		eval($spyClassCode);
		$spyClass = new \Wmde\SpyGenerator\Test\Generated\BooleanOrderSpy($this->newOrderFixture());

		$this->assertTrue($spyClass->getFulfilled());
	}

	private function newOrderFixture(): Order {
		$order = new Order('1');
		$order->addItem('Test item', 1000);
		$order->applyRebate(0.2);
		$order->fulfill('Fulfilled by Joe');
		return $order;
	}
}

