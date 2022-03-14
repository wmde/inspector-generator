<?php

declare(strict_types=1);

namespace Wmde\SpyGenerator;

use LogicException;
use Nette\NotImplementedException;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Method;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\PsrPrinter;
use phpDocumentor\Reflection\DocBlock\Tags\Var_;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;

class SpyGenerator
{
	public function __construct(private string $namespace)
	{
		// TODO add header comments
	}

	/**
	 * @param class-string $className
	 * @param string $spyName
	 */
	public function generateSpy(string $className, string $spyName): string
    {
		$namespace = new PhpNamespace($this->namespace);
		$reflectedClass = new ReflectionClass($className);
		$namespace->addUse($reflectedClass->getName());
		$spyClass = $namespace->addClass($spyName);
		$this->createProperties($spyClass, $reflectedClass->getShortName());
		$this->createConstructor($spyClass, $className);
		$this->createAccessors($spyClass, $className);

		$printer = new PsrPrinter();
		return $printer->printNamespace($namespace);
	}

	private function createProperties(ClassType $spyClass, string $shortClassName): void
    {
		$spyClass->addProperty('reflectedClass')
		   ->setType('\ReflectionClass')
	   	   ->setComment("@var \\ReflectionClass<$shortClassName>")
		   ->setPrivate();
	}

	private function createConstructor(ClassType $spyClass, string $className): void
    {
		$constructor = $spyClass->addMethod('__construct')
			->setBody('$this->reflectedClass = new \ReflectionClass($inspectionObject);');
		$constructor->addPromotedParameter('inspectionObject')
			  ->setType($className);
	}

	/**
	 * @param ClassType $spyClass
	 * @param class-string $className
	 */
	private function createAccessors(ClassType $spyClass, string $className): void
    {
		$this->createGetValueMethod($spyClass);
		$reflectedClass = new ReflectionClass($className);
		// TODO use loop
		$prop = $reflectedClass->getProperty('fulfilled');
		$this->createAccessor($spyClass, $prop);
		$prop = $reflectedClass->getProperty('amount');
		$this->createAccessor($spyClass, $prop);
		$prop = $reflectedClass->getProperty('comment');
		$this->createAccessor($spyClass, $prop);
		$prop = $reflectedClass->getProperty('rebate');
		$this->createAccessor($spyClass, $prop);
		$prop = $reflectedClass->getProperty('items');
		$this->createAccessor($spyClass, $prop);
	}

	private function createGetValueMethod(ClassType $spyClass): void
    {
		$spyClass->addMethod('getPrivateValue')
		   ->setPrivate()
		   ->setReturnType('mixed')
			 ->addBody('$prop = $this->reflectedClass->getProperty($propertyName);')
			 ->addBody('$prop->setAccessible(true);')
			 ->addBody('return $prop->getValue($this->inspectionObject);')
			 ->addParameter('propertyName')
		 ->setType('string');
	}

	private function createAccessor(ClassType $spyClass, ReflectionProperty $prop): void
    {
		$name = $prop->getName();
		$accessorName = 'get' . ucfirst($name);
		[$returnType, $typeAssertion] = $this->getAccessorType($prop);
		$accessorMethod = $spyClass->addMethod($accessorName)
			 ->setReturnType($returnType);
		$accessorMethod->setBody('return $this->getPrivateValue(?);', [$name]);
		if ($typeAssertion) {
			$accessorMethod
				 ->setBody('$value = $this->getPrivateValue(?);', [$name])
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
		$typeAssertion = '';
		// TODO $propertyType->allowsNull check here
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
		return [ $propertyType->getName(), $typeAssertion ];
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
		// empty check to make Psalm happy
		$typeName = $tag->getType();
		if (!$typeName) {
			return;
		}
		$method->setComment('@psalm-suppress MixedReturnTypeCoercion')
			->addComment('@return ' .	$typeName);
	}
}
