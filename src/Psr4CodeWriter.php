<?php

declare(strict_types=1);

namespace WMDE\InspectorGenerator;

use LogicException;

class Psr4CodeWriter implements CodeWriter
{
	private const PHP_INTRO = "<?php\ndeclare(strict_types=1);\n\n";

	/**
	 * @param array<string, string> $psr4Mappings
	 */
	public function __construct(private array $psr4Mappings)
	{
		foreach (array_keys($psr4Mappings) as $namespacePrefix) {
			if (substr($namespacePrefix, -1) !== "\\") {
				throw new \InvalidArgumentException("Namespace prefixes must end with a separator");
			}
		}
	}

	public function writeResult(GeneratedInspectorResult $result): string
    {
		[$prefix, $destinationPath] = $this->getNamespaceMapForClass($result->fullyQualifiedClassName);
		$filename = $this->getFilenameForClass($result->fullyQualifiedClassName, $prefix, $destinationPath);
		$this->createDirectory($filename);
		$this->doWrite($result, $filename);
		return $filename;
	}

	private function createDirectory(string $filename): void
    {
		$path = dirname($filename);
		if (file_exists($path)) {
			return;
		}
		if (!mkdir($path, 0755, true)) {
			throw new \RuntimeException("Could not create directory $path");
		}
	}

	private function doWrite(GeneratedInspectorResult $result, string $filename): void
    {
		if (file_put_contents($filename, self::PHP_INTRO . $result->code) === false) {
			throw new \RuntimeException("Could not write to file $filename");
		}
	}

	/**
	 * @return array{string,string}
	 */
	private function getNamespaceMapForClass(string $fullyQualifiedClassName): array
    {
		foreach ($this->psr4Mappings as $namespacePrefix => $destinationPath) {
			if (strpos($fullyQualifiedClassName, $namespacePrefix) === 0) {
				return [ $namespacePrefix, $destinationPath ];
			}
		}
		throw new LogicException("No PSR-4 namespace prefix mapping found for {$fullyQualifiedClassName}");
	}

	private function getFilenameForClass(
        string $fullyQualifiedClassName,
        string $namespacePrefix,
		string $destinationPath
    ): string {
		$normalizedDestinationPath = preg_replace('!/+$!', '', $destinationPath);
		$classNameWithoutPrefix = substr($fullyQualifiedClassName, strlen($namespacePrefix));
		$psr4Path = strtr($classNameWithoutPrefix, '\\', '/') . '.php';
		return $normalizedDestinationPath . '/' . $psr4Path;
	}
}
