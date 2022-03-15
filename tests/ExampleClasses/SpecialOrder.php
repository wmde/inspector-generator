<?php

declare(strict_types=1);

namespace Wmde\InspectorGenerator\Tests\ExampleClasses;

class SpecialOrder extends Order
{
	private string $specialSauce = 'Lots of love';

	public function addLove(): void
	{
		$this->specialSauce = "Lots and l" . substr($this->specialSauce, 1);
	}

	public function duplicate(): Order
    {
		$order = parent::duplicate();
		if ($order instanceof SpecialOrder) {
			$order->specialSauce = $this->specialSauce;
        }
		return $order;
	}
}
