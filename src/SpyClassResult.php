<?php

declare(strict_types=1);

namespace Wmde\SpyGenerator;

final class SpyClassResult
{
	public function __construct(
		/** @readonly */
		public string $code,
		/** @readonly */
		public string $fullyQualifiedClassName
	) {
	}
}
