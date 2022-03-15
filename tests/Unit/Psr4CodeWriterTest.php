<?php

declare(strict_types=1);

namespace Wmde\SpyGenerator\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Wmde\SpyGenerator\Psr4CodeWriter;
use Wmde\SpyGenerator\SpyClassResult;

/**
 * @covers \Wmde\SpyGenerator\Psr4CodeWriter
 */
class Psr4CodeWriterTest extends TestCase
{
	public function test_constructor_enforces_namespace_separators(): void
	{
		$this->expectException(\InvalidArgumentException::class);

		new Psr4CodeWriter([
			'Tests\\' => 'test',
			'Code' => 'src'
		]);
	}

	public function test_writer_writes_to_file(): void
    {
		$destinationPath = sys_get_temp_dir() . '/' . uniqid('test_');
		$writer = new Psr4CodeWriter([ "WMDE\\SpyGenerator\\Tests\\" => $destinationPath]);

		$filename = $writer->writeResult(new SpyClassResult(
			'// No code here',
			'WMDE\SpyGenerator\Tests\FirstNamespace\SubNamespace\TestClass'
		));

		$this->assertSame("$destinationPath/FirstNamespace/SubNamespace/TestClass.php", $filename);
		$this->assertFileExists($filename);
	}

	public function test_writer_writes_executable_php_code_to_file(): void
    {
		$destinationPath = sys_get_temp_dir() . '/' . uniqid('test_');
		$writer = new Psr4CodeWriter([ "WMDE\\SpyGenerator\\Tests\\" => $destinationPath]);

		$filename = $writer->writeResult(new SpyClassResult(
			'echo "Test succeeded!";',
			'WMDE\SpyGenerator\Tests\TestCode'
		));
		$this->expectOutputString('Test succeeded!');

		/**
		 * @psalm-suppress UnresolvableInclude
		 */
		require $filename;
	}

	// TODO test exceptional cases (dir not writable, file not writable, dir already exists)
}
