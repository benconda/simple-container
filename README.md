A simple dependency injection container, using reflection for dependency injection.

# Container setup

Simply instanciate the container : 
```php
$container = new \Benconda\SimpleContainer\Container();

// If you need some parameters to be injected (scalar values) you have to feed the container :
$container->setParameter('sampleParameter', 'sampleValue');
```

## Usage

Let's start with basic usage example

```php
interface SomeInterface {}

class Foo {
    public function __construct(public string $sampleParameter)
    {
    }
}

class Bar {
    public function __construct(public Foo $foo)
    {
    }
}

$container = new \Benconda\SimpleContainer\Container();
// The ChildClass2 parameter cannot be guessed, here we specify the parameter value
$container->setParameter('sampleParameter', 'sampleValue');
// Now I can get the Bar instance like this 🔥
$bar = $container->get(Bar::class);
// Foo instance is automatically injected too
$foo = $bar->foo
// The same Foo instance will be returned by the container
$sameFoo = $container->get(Foo::class);
// The parameter is injected too
$foo->sampleParameter
```
As you can see, I didn't configure anything about services classes, it just works.

## How it works
Under the hood, it relies on PHP Reflection to automatically create instance of object. And recursively do the same for class dependencies.

## Deal with interfaces implementations
Because the magic have its limits, the container cannot guess your interfaces implementations :

```php
interface MyInterface {}

class Foo implements MyInterface {

}

class Bar implements MyInterface {
}

class Baz {
    public function __construct(public MyInterface $implementation) {}
}

$container = new \Benconda\SimpleContainer\Container();
// ❌ Will throw a \Benconda\SimpleContainer\UndefinedImplementationException
$container->get(Baz::class);
// We have to tell the container the wanted implementation
$container->setImplementation(MyInterface::class, Foo::class);
// Now it works
$baz = $container->get(Baz::class);
$baz->implementation === $container->get(Foo::class) // true
```
## Circular dependencies
The container have a protection against circular dependencies :
```php
final class CircularDependency1
{
    public function __construct(private CircularDependency2 $circularDependency2)
    {
    }
}

final class CircularDependency2
{
    public function __construct(private CircularDependency1 $circularDependency1)
    {
    }
}

$container = new \Benconda\SimpleContainer\Container();
// ❌ Will throw a \Benconda\SimpleContainer\CircularDependencyException
$container->get(CircularDependency1::class);
```

## Function injection
It's possible to auto-inject function parameters like this : 

```php
$injectionResult = $container->inject(fn (ChildClass1 $childClass1) => "Injected parameter is " . get_class($childClass1));
// $injectionResult === 'InjectedParameter is ChildClass1'
```
It currently supports calling : 
* Invokable $objects (__invoke method is called on the $object with injected parameters)
* Static method
* Function (anonymous / Closure / string reference)

## What's next ? 
### Scoped DI
With the rise of long-running PHP process (Swoole, FrankenPhp, React PHP) could be good to scope DI, in a hierarchical way. So we can reset services in this scope to release memory, for example : 
```php
$container->span('incomingRequest');
$container->get(...);
$container->get(...);
$container->get(...);

$container->endSpan('incomingRequest'); // Will reset any created instance since the span creation
```
### Better interface handling
We have to set up interfaces implementation manually. Could be great to have a kind of auto interface guessing. But it's a challenge can be a performance bottleneck (need to find all interfaces implementations).

Another related things is to be able to fetch all classes names under an interface. (or leverage attributes to do so).
All these smart things have a drawback, need to parse and fetch files in a directory, and will require kind of compilation time to avoid runtime bottleneck.