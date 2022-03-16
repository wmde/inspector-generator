<?php

declare(strict_types=1);

namespace WMDE\InspectorGenerator;

interface CodeWriter
{
	public function writeResult(GeneratedInspectorResult $result): string;
}
