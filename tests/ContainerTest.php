<?php

declare(strict_types=1);

namespace BenCondaTest\SimpleContainer;

use Benconda\SimpleContainer\Container;
use Benconda\SimpleContainer\Exception\CircularDependencyException;
use Benconda\SimpleContainer\Exception\MissingTypeException;
use Benconda\SimpleContainer\Exception\UndefinedImplementationException;
use Benconda\SimpleContainer\Exception\UndefinedParameterException;
use PHPUnit\Framework\TestCase;

final class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    public function testDependencyInjection(): void
    {
        $this->container->setParameter('sampleParameter', 'foo');
        $mainClass = $this->container->get(MainClass::class);
        $this->assertInstanceOf(MainClass::class, $mainClass);
        $this->assertInstanceOf(ChildClass1::class, $mainClass->childClass1);
        $this->assertInstanceOf(ChildClass3::class, $mainClass->childClass3);
        $this->assertInstanceOf(ChildClass2::class, $mainClass->childClass3->childClass2);
        $this->assertSame('foo', $mainClass->childClass3->childClass2->sampleParameter);
    }

    public function testWithInterface(): void
    {
        $this->container->setImplementation(SomeInterface::class, ChildClass1::class);
        $someInterface = $this->container->get(SomeInterface::class);
        $this->assertInstanceOf(ChildClass1::class, $someInterface);
    }

    public function testWithInjectedInterface(): void
    {
        $this->container->setImplementation(SomeInterface::class, ChildClass1::class);
        $object = $this->container->get(ClassWithInjectedInterface::class);
        self::assertInstanceOf(ChildClass1::class, $object->realImplementation);
    }

    public function testWithInterfaceWithoutImplementation(): void
    {
        self::expectException(UndefinedImplementationException::class);
        $this->container->get(SomeInterface::class);
    }

    public function testWithUnknownParameter(): void
    {
        self::expectExceptionObject(
            new UndefinedParameterException(
                parameterName: 'unknownParameter',
                fromClassConstructor: ClassWithParameter::class
            )
        );

        $this->container->get(ClassWithParameter::class);
    }

    public function testCircularDependency(): void
    {
        self::expectExceptionObject(
            new CircularDependencyException(
                class: CircularDependency1::class,
                hierarchy: [CircularDependency1::class, CircularDependency2::class]
            )
        );

        $this->container->get(CircularDependency1::class);
    }

    public function testNoTypeForParameter(): void
    {
        self::expectExceptionObject(
            new MissingTypeException(
                class: NoParameterType::class,
                parameterName: 'IHaveNoType'
            )
        );

        $this->container->get(NoParameterType::class);
    }

    public function testInjection(): void
    {
        $injectionResult = $this->container->inject(fn (ChildClass1 $childClass1) => get_class($childClass1));

        self::assertSame(ChildClass1::class, $injectionResult);
    }

    public function testInjectionWithInvokableClass()
    {
        $invokableClass = new InvokableClassInjection();
        $this->container->setImplementation(SomeInterface::class, ChildClass1::class);
        $injectionResult = $this->container->inject($invokableClass);

        self::assertSame(ChildClass1::class, $injectionResult);
    }
}

final class ClassWithParameter
{
    public function __construct(private string $unknownParameter)
    {
    }
}

interface SomeInterface
{
}

final class ChildClass1 implements SomeInterface
{
}

final class ChildClass2 implements SomeInterface
{
    public function __construct(public string $sampleParameter)
    {
    }
}

final class ChildClass3
{
    public function __construct(public readonly ChildClass2 $childClass2)
    {
    }
}

final class MainClass
{

    public function __construct(
        public readonly ChildClass1 $childClass1,
        public readonly ChildClass3 $childClass3
    )
    {
    }
}

final class ClassWithInjectedInterface
{
    public function __construct(
        public readonly SomeInterface $realImplementation
    )
    {
    }
}

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

final class NoParameterType
{
    public function __construct($IHaveNoType)
    {
    }
}

final class InvokableClassInjection
{
    public function __invoke(SomeInterface $implementation)
    {
        return get_class($implementation);
    }
}