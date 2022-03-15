<?php

declare(strict_types=1);

namespace Wmde\InspectorGenerator;

interface CodeWriter
{
	public function writeResult(GeneratedInspectorResult $result): string;
}
