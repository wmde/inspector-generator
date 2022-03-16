<?php

declare(strict_types=1);

namespace WMDE\InspectorGenerator\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WMDE\InspectorGenerator\Psr4CodeWriter;
use WMDE\InspectorGenerator\GeneratedInspectorResult;

/**
 * @covers \WMDE\InspectorGenerator\Psr4CodeWriter
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
		$writer = new Psr4CodeWriter([ "WMDE\\InspectorGenerator\\Tests\\" => $destinationPath]);

		$filename = $writer->writeResult(new GeneratedInspectorResult(
			'// No code here',
			'WMDE\InspectorGenerator\Tests\FirstNamespace\SubNamespace\TestClass'
		));

		$this->assertSame("$destinationPath/FirstNamespace/SubNamespace/TestClass.php", $filename);
		$this->assertFileExists($filename);
	}

	public function test_writer_writes_executable_php_code_to_file(): void
    {
		$destinationPath = sys_get_temp_dir() . '/' . uniqid('test_');
		$writer = new Psr4CodeWriter([ "WMDE\\InspectorGenerator\\Tests\\" => $destinationPath]);

		$filename = $writer->writeResult(new GeneratedInspectorResult(
			'echo "Test succeeded!";',
			'WMDE\InspectorGenerator\Tests\TestCode'
		));
		$this->expectOutputString('Test succeeded!');

		/**
		 * @psalm-suppress UnresolvableInclude
		 */
		require $filename;
	}

	// TODO test exceptional cases (dir not writable, file not writable, dir already exists)
}
