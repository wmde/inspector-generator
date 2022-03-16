# PHP introspection class generator

This utility generates type-safe "Inspector" classes for classes with
hard-to-access private properties, for use in white-box tests.

The benefit of using generated classes is their type-safety. Your IDE
and the static type checkers won't complain about undefined properties or
mixed return types.

### When should I use this?

You should not use the inspector classes inside your production code,
*only inside tests*! Even in tests, it's a code smell, because it makes
your tests rely on implementation details that are encapsulated for a
reason! Only use this library as a last resort, for example for testing
the (de)serialiation of your persistence layer.

## Installation

	composer require wmde/inspector-generator

## Setting up the class generation

Before you can use the generated classes in you tests, you need to
generate them. Decide on a file path and namespace where to put them and
create a PHP script, e.g. `tests/generate_inspectors.php` that contains code
like this:

```php

$generator = new WMDE\InspectorGenerator\InspectorGenerator('App\Tests\Inspectors');
$writer = new WMDE\InspectorGenerator\Psr4Writer(['App\Tests\\' => __DIR__]);

// Create inspectors for your classes
$writer->writeResult($generator->generateInspector('App\Foo\SomeObject', 'SomeObjectInspector'));
$writer->writeResult($generator->generateInspector('App\Bar\OtherObject', 'OtherObjectPeeker'));
// ... etc

```

This will create the classes `App\Tests\Inspectors\SomeObjectInspector` and
`App\Tests\Inspectors\OtherObjectPeeker` in the directory `tests/Inspectors/`.

The example shows that you can name the inspector classes however you like, but
in your own code we recommend using a consistent suffix.

Run the script once to generate the inspector classes. Afterwards, you can use the
generated inspector classes in your tests.

## Using the inspector classes in your tests

In your tests, you use the inspector class to inspect the internal state of
your object in when doing assertions. 

In the following code example, `state` is a private property of the `SomeObject` class.
With the generated inspector class for `SomeObject`, you can look directly at the state:

```php

public function testSomeObjectChangesState(): void {
	// Arrange
	$myObject = new SomeObject();
	
	// Act
	$myObject->transitionToABetterState();

	// Assert
	$inspector = new SomeObjectInspector($myObject);
	$this->assertSame(States::NIRVANA, $inspector->getState());
}

```

### What accessors are in the inspector class?

- Each `private` or `protected` property of the inspected object will get an
	accessor method with the prefix `get`. Example: An object with the
	properties `amount`, `name` and `numItems` will generate an inspector
	class with the type-safe accessor methods `getAmount()`, `getName()`
	and `getNumItems()`.
- If a property has a docblock with a `@var` type annotation, the
	generator will use that type annotation to generate a `@return`
	annotation for the accessor.
- Static properties will get accessors as well.
- The generator ignores existing accessor methods of the inspected object
	and will generate accessors for *all* properties.
- When using inheritance, the generator will only generate accessors 
    for `protected` properties of the parent class.


## Keeping the inspector classes in sync with your code

Whenever you refactor the properties of the inspected classes you'll need
to re-generate the inspector classes, to avoid failing tests. You can do
this ad-hoc whenever needed or as part of the bootstrapping of your test
environment.

You can decide if you want to check in the generated files in your
revision control software or not. The tradeoff is between performance
(spending CPU cycles to generate the files over and over) and storage.

## Integrating with your CI tools

The generated inspector classes should trigger no errors or warnings with
the static analysis tools [PHPStan](https://phpstan.org/) and
[Psalm](https://psalm.dev/). If you get errors for the inspector classes
from these tools, please open an issue.

If you don't check the generated inspector classes into your revision
control software, you need to run the generation script before running the
tests. You can do this as a separate task in your CI pipeline or as part
of the bootstrapping of your test environment.

If you do check the generated inspector classes into your revision control
software, you might be able to automate the re-generation on your local
machine, e.g. with [Git hooks](https://git-scm.com/book/en/v2/Customizing-Git-Git-Hooks)

The generated classes follow the
[PSR-4](https://www.php-fig.org/psr/psr-4/) code layout and the
[PSR-12](https://www.php-fig.org/psr/psr-12/) coding style. If your code
style checker expects a different style, it's best for now to exclude the
generated classes. Future versions might add different formatters.

## Possible features for the future

- Add CI for this project, using GitHub Actions
- Add configurable code comments to file header (warning that this is an
	autogenerated file, adding copyright information, etc)
- Allow partial inspectors (specify needed properties)
- Allow custom formatters for different coding styles (subclass
   [Printer](https://github.com/nette/php-generator/blob/master/src/PhpGenerator/Printer.php)
   of the [Nette code generation library](https://doc.nette.org/en/php-generator) or 
   create custom implementation ).
- Create PHAR executable that uses a YAML config file (instead of writing
	your own PHP script)
