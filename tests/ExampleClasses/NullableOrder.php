<?php

declare(strict_types=1);

namespace WMDE\InspectorGenerator\Tests\ExampleClasses;

/**
 * This is an example class with all-nullable properties.
 *
 * This is just for testing the generation code, in your own code
 * you should avoid nullable properties as much as possible!
 *
 * @psalm-consistent-constructor
 */
class NullableOrder implements OrderInterface
{
	protected ?bool $fulfilled = null;
	protected ?int $amount = null;
	/**
	 * @var string[]
	 */
	protected ?array $items = null;
	protected ?string $comment = null;
	protected ?Order $previous = null;
	protected ?float $rebate = null;

	public function __construct(
		protected string $id
	) {
	}

	public function addItem(string $name, int $amount): void
	{
		if ($this->items === null) {
			$this->items = [];
		}
		$this->items[] = $name;
		$this->amount = ($this->amount ?: 0) + $amount;
	}

	public function applyRebate(float $rebate): void
    {
		$this->rebate = $rebate;
	}

	public function fulfill(string $comment = '', ?Order $previous = null): void
    {
		if ($this->fulfilled) {
			throw new \LogicException('Order is already fulfilled!');
		}
		$this->fulfilled = true;
		$this->comment = $comment;
		$this->previous = $previous;
	}

	public function duplicate(): NullableOrder
    {
		$order = new static($this->id);
		$order->fulfilled = $this->fulfilled;
		$order->comment = $this->comment;
		$order->items = $this->items;
		$order->previous = $this->previous;
		$order->rebate = $this->rebate;
		return $order;
	}
}
