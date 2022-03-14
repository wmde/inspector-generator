<?php
declare(strict_types=1);

namespace Wmde\SpyGenerator;

class SpyGenerator {
	public function generateSpy( string $className, string $spyName ): string {
		return 'namespace Wmde\SpyGenerator\Test\Generated; class BooleanOrderSpy { function getFulfilled(){return true;}}';
	}
}

