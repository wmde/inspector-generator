<?php

declare(strict_types=1);

namespace WMDE\InspectorGenerator;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PsrPrinter;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;

class InspectorGenerator
{
	public function __construct(private string $namespace)
	{
	}

	/**
	 * @param class-string $inspectedClassName
	 * @param string $inspectorClassName Class name without namespace
	 */
	public function generateInspector(string $inspectedClassName, string $inspectorClassName): GeneratedInspectorResult
    {
		$namespace = new PhpNamespace($this->namespace);

		$reflectedClass = new ReflectionClass($inspectedClassName);
		$namespace->addUse($reflectedClass->getName());

		$inspectorClass = $namespace->addClass($inspectorClassName);
		$this->createProperties($inspectorClass, $reflectedClass->getShortName());
		$this->createConstructor($inspectorClass, $inspectedClassName);
		$this->createAccessors($inspectorClass, $inspectedClassName);

		$printer = new PsrPrinter();
		return new GeneratedInspectorResult(
			$printer->printNamespace($namespace),
			$this->namespace . '\\' . $inspectorClassName
		);
	}

	private function createProperties(ClassType $inspectorClass, string $shortClassName): void
    {
		$inspectorClass->addProperty('reflectedClass')
		   ->setType('\ReflectionClass')
	   	   ->setComment("@var \\ReflectionClass<$shortClassName>")
		   ->setPrivate();
	}

	private function createConstructor(ClassType $inspectorClass, string $className): void
    {
		$constructor = $inspectorClass->addMethod('__construct')
			->setBody('$this->reflectedClass = new \ReflectionClass($inspectionObject);');
		$constructor->addPromotedParameter('inspectionObject')
				->setPrivate()
			  ->setType($className);
	}

	/**
	 * @param ClassType $inspectorClass
	 * @param class-string $className
	 */
	private function createAccessors(ClassType $inspectorClass, string $className): void
    {
		$this->createGetValueMethod($inspectorClass);
		$reflectedClass = new ReflectionClass($className);
		$propertyFilter = ReflectionProperty::IS_PROTECTED
			| ReflectionProperty::IS_PRIVATE
			| ReflectionProperty::IS_READONLY;
		foreach ($reflectedClass->getProperties($propertyFilter) as $prop) {
			$this->createAccessor($inspectorClass, $prop);
		}
	}

	private function createGetValueMethod(ClassType $inspectorClass): void
    {
		$inspectorClass->addMethod('getPrivateValue')
		   ->setPrivate()
		   ->setReturnType('mixed')
		   ->addBody('if (!$this->reflectedClass->hasProperty($propertyName)) {')
		   ->addBody('    throw new \LogicException(sprintf(')
		   ->addBody('        "Property %s not found in class %s. Try re-generating the class %s",')
		   ->addBody('        $propertyName, $this->reflectedClass->getName(), self::class')
	   	   ->addBody('    ));')
	   	   ->addBody('}')
		   ->addBody('$prop = $this->reflectedClass->getProperty($propertyName);')
		   ->addBody('$prop->setAccessible(true);')
		   ->addBody('return $prop->getValue($this->inspectionObject);')
		   ->addParameter('propertyName')
		   ->setType('string');
	}

	private function createAccessor(ClassType $inspectorClass, ReflectionProperty $prop): void
    {
		$name = $prop->getName();
		$accessorName = 'get' . ucfirst($name);
		[$returnType, $typeAssertion] = $this->getAccessorType($prop);
		$accessorMethod = $inspectorClass->addMethod($accessorName)
			 ->setReturnType($returnType);
		$accessorMethod->setBody('return $this->getPrivateValue(?);', [$name]);
		if ($typeAssertion) {
			$accessorMethod
				->setBody('')
				->addBody('$value = $this->getPrivateValue(?);', [$name])
				->addBody("assert($typeAssertion);")
				->addBody('return $value;');
		}
		$this->addTypeHintForAccessor($accessorMethod, $prop);
	}

	/**
	 * @return array{string,string}
	 */
	private function getAccessorType(ReflectionProperty $prop): array
    {
		$propertyType = $prop->getType();
		if (!($propertyType instanceof ReflectionNamedType)) {
			return ['mixed', ''];
		}
		$returnType = $propertyType->getName();
		if ($propertyType->allowsNull()) {
			$returnType = "?$returnType";
		}
		$typeAssertion =  '$value instanceof \\' . $propertyType->getName();

		if ($propertyType->isBuiltin()) {
			$typeAssertion = match ($propertyType->getName()) {
				'bool' => 'is_bool($value)',
				'int' => 'is_int($value)',
				'string' => 'is_string($value)',
				'float' => 'is_float($value)',
				'array' => 'is_array($value)',
				default => ''
			};
		}

		if ($propertyType->allowsNull()) {
			$typeAssertion = "\$value === null || $typeAssertion";
		}

		return [ $returnType, $typeAssertion ];
	}

	public function addTypeHintForAccessor(Method $method, ReflectionProperty $prop): void
    {
		$propertyType = $prop->getType();
		if (!($propertyType instanceof ReflectionNamedType) || $propertyType->getName() !== 'array') {
			return;
		}
		$docComment = $prop->getDocComment();
		if (empty($docComment)) {
			return;
		}

		$factory  = \phpDocumentor\Reflection\DocBlockFactory::createInstance();
		$docBlock = $factory->create($docComment);
		$tags = $docBlock->getTagsByName('var');
		if (count($tags) === 0) {
			return;
		}
		$tag = $tags[0];
		// type check to make PHPStan happy
		if (!($tag instanceof Var_)) {
			return;
		}
		$typeName = $tag->getType();
		// empty check to make Psalm happy
		if (!$typeName) {
			return;
		}
		$method->setComment('@psalm-suppress MixedReturnTypeCoercion')
			->addComment('@return ' . $typeName);
	}
}
