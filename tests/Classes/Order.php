<?php

declare(strict_types=1);

namespace Wmde\SpyGenerator\Tests\Classes;

/**
 * This is an example class of a "write-only" entity that has state changes, but does not expose its properties.
 */
class Order
{
	private bool $fulfilled;
	private int $amount;
	/**
	 * @var string[]
	 */
	private array $items;
	private string $comment;
	private ?Order $previous = null;
	private float $rebate;

	public function __construct(
		private string $id
	) {
		$this->fulfilled = false;
		$this->comment = '';
		$this->amount = 0;
		$this->rebate = 0.0;
		$this->items = [];
	}

	public function addItem(string $name, int $amount): void
    {
		$this->items[] = $name;
		$this->amount += $amount;
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

	public function duplicate(): self
    {
		$order = new self($this->id);
		$order->fulfilled = $this->fulfilled;
		$order->comment = $this->comment;
		$order->items = $this->items;
		$order->previous = $this->previous;
		$order->rebate = $this->rebate;
		return $order;
	}
}
