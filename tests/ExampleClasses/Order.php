<?php

declare(strict_types=1);

namespace WMDE\InspectorGenerator\Tests\ExampleClasses;

/**
 * This is an example class of a "write-only" entity that has state changes, but does not expose its properties.
 *
 * @psalm-consistent-constructor
 */
class Order implements OrderInterface
{
	private static string $prefix = "O-";

	protected bool $fulfilled;
	protected int $amount;
	/**
	 * @var string[]
	 */
	protected array $items;
	protected ?Order $previous = null;
	protected float $rebate;
	private string $comment;

	public function __construct(
		protected string $id
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

	public function duplicate(): Order
    {
		$order = new static($this->id);
		$order->fulfilled = $this->fulfilled;
		$order->comment = $this->comment;
		$order->items = $this->items;
		$order->previous = $this->previous;
		$order->rebate = $this->rebate;
		return $order;
	}

	public static function setNewPrefixForAllOrders(string $prefix): void
    {
		self::$prefix = $prefix;
	}

	/**
	 * This method could be used to "access" the prefix.
	 * It's just here to make the static analyzers happy
	 */
	public static function prefixRandomString(string $someString): string
    {
		return self::$prefix . $someString;
	}
}
