<?php

declare(strict_types=1);

namespace Wmde\SpyGenerator\Tests\ExampleClasses;

/**
 * This constructor interface exists to make PHPStan happy.
 *
 * @see https://phpstan.org/blog/solving-phpstan-error-unsafe-usage-of-new-static
 */
interface OrderInterface
{
	public function __construct(string $id);
}
