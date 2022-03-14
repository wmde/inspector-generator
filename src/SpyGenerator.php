<?php
declare(strict_types=1);

namespace Wmde\SpyGenerator;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Printer;
use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;

class SpyGenerator {

	public function __construct( private string $namespace )
	{
		// TODO add header comments
	}

	/**
	 * @param class-string $className
	 * @param string $spyName
	 */
	public function generateSpy( string $className, string $spyName ): string {
		$namespace = new PhpNamespace($this->namespace);
		$reflectedClass = new ReflectionClass($className);
		$namespace->addUse($reflectedClass->getName());
		$spyClass = $namespace->addClass($spyName);
		$this->createProperties($spyClass, $reflectedClass->getShortName());
		$this->createConstructor($spyClass, $className);
		$this->createAccessors($spyClass, $className);
		
		$printer = new Printer();
		return $printer->printNamespace($namespace);
	}

	private function createProperties(ClassType $spyClass, string $shortClassName): void {
		$spyClass->addProperty('reflectedClass')
		   ->setType('\ReflectionClass')
	   	   ->setComment("@var \\ReflectionClass<$shortClassName>")
		   ->setPrivate();
	}

	private function createConstructor(ClassType $spyClass, string $className): void {
		$constructor = $spyClass->addMethod('__construct')
			->setBody('$this->reflectedClass = new \ReflectionClass($inspectionObject);');
		$constructor->addPromotedParameter('inspectionObject')
			  ->setType( $className );
	}

	/**
	 * @param ClassType $spyClass
	 * @param class-string $className
	 */
	private function createAccessors(ClassType $spyClass, string $className): void {
		$this->createGetValueMethod($spyClass);
		$reflectedClass = new ReflectionClass($className);
		// TODO use loop
		$prop = $reflectedClass->getProperty('fulfilled');
		$this->createAccessor($spyClass, $prop);
	}

	private function createGetValueMethod(ClassType $spyClass): void {
		$spyClass->addMethod('getPrivateValue')
		   ->setPrivate()
		   ->setReturnType('mixed')
			 ->addBody('$prop = $this->reflectedClass->getProperty($propertyName);' )
			 ->addBody('$prop->setAccessible(true);')
			 ->addBody('return $prop->getValue($this->inspectionObject);')
			 ->addParameter('propertyName')
		 ->setType('string');
	   		
	}

	private function createAccessor(ClassType $spyClass, ReflectionProperty $prop): void {
		$name = $prop->getName();
		$accessorName = 'get'.ucfirst($name);
		[$returnType, $typeAssertion] = $this->getAccessorType($prop);
		$spyClass->addMethod($accessorName)
			 ->setReturnType($returnType)
			 ->addBody('$value = $this->getPrivateValue(?);', [$name] )
			 ->addBody("assert($typeAssertion);")
			 ->addBody('return $value;');

	}

	/**
	 * @return array{string,string}
	 */
	private function getAccessorType(ReflectionProperty $prop): array {
		$propertyType = $prop->getType();
		if ( !($propertyType instanceof ReflectionNamedType) ) {
			return ['mixed', ''];
		}
		return [
			$propertyType->getName(),
			 // TODO make dynamic, based on $prop->getType
			'is_bool($value)'
		];

	}


}

