<?php

declare(strict_types=1);

namespace Wmde\SpyGenerator;

interface CodeWriter
{
	public function writeResult(SpyClassResult $result): string;
}
