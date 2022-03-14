<?php
declare(strict_types=1);

namespace Wmde\SpyGenerator;

use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Printer;
use ReflectionClass;
use ReflectionProperty;

class SpyGenerator {

	public function __construct( private string $namespace )
	{
		// TODO add header comments
	}

	public function generateSpy( string $className, string $spyName ): string {
		$namespace = new PhpNamespace($this->namespace);
		$reflectedClass = new ReflectionClass($className);
		$namespace->addUse($reflectedClass->getName());
		$spyClass = $namespace->addClass($spyName);
		$this->createProperties($spyClass);
		$this->createConstructor($spyClass, $className);
		$this->createAccessors($spyClass, $className);
		
		$printer = new Printer();
		return $printer->printNamespace($namespace);
	}

	private function createProperties(ClassType $spyClass): void {
		$spyClass->addProperty('reflectedClass')
		   ->setType('\ReflectionClass')
		   ->setPrivate();
	}

	private function createConstructor(ClassType $spyClass, string $className): void {
		$constructor = $spyClass->addMethod('__construct')
			->setBody('$this->reflectedClass = new \ReflectionClass($inspectionObject);');
		$constructor->addPromotedParameter('inspectionObject')
			  ->setType( $className );
	}

	private function createAccessors(ClassType $spyClass, string $className): void {
		$reflectedClass = new ReflectionClass($className);
		// TODO use loop
		$prop = $reflectedClass->getProperty('fulfilled');
		$this->createAccessor($spyClass, $prop);
	}

	private function createAccessor(ClassType $spyClass, ReflectionProperty $prop): void {
		$name = $prop->getName();
		$accessorName = 'get'.ucfirst($name);
		$type = $prop->getType() ? $prop->getType()->getName() : 'mixed';
		$spyClass->addMethod($accessorName)
			 ->setReturnType($type)
			 ->addBody('$prop = $this->reflectedClass->getProperty(?);', [$name] )
			 ->addBody('$prop->setAccessible(true);')
			 ->addBody('$value = $prop->getValue($this->inspectionObject);')
			 // TODO make dynamic, based on $prop->getType
			 ->addBody('assert(is_bool($value));')
			 ->addBody('return $value;');

	}


}

