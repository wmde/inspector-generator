<?php

declare(strict_types=1);

namespace Wmde\InspectorGenerator;

final class GeneratedInspectorResult
{
	public function __construct(
		/** @readonly */
		public string $code,
		/** @readonly */
		public string $fullyQualifiedClassName
	) {
	}
}
