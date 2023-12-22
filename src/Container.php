<?php

declare(strict_types=1);

namespace Benconda\SimpleContainer;

use Benconda\SimpleContainer\Exception\CircularDependencyException;
use Benconda\SimpleContainer\Exception\MissingTypeException;
use Benconda\SimpleContainer\Exception\NotFoundException;
use Benconda\SimpleContainer\Exception\UndefinedImplementationException;
use Benconda\SimpleContainer\Exception\UndefinedParameterException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

final class Container implements ContainerInterface
{

    private array $classContainer = [];
    private array $parameterContainer = [];
    private array $aliases = [];
    private array $circularDependencyCheck = [];

    public function reset(): void
    {
        $this->classContainer = $this->parameterContainer = $this->aliases = $this->circularDependencyCheck = [];
    }

    /**
     * @template T
     * @param class-string<T> $id
     *
     * @return T of object
     */
    public function get(string $id): mixed
    {
        $className = $this->getClass($id);

        if (in_array($id, $this->circularDependencyCheck, true)) {
            throw new CircularDependencyException($id, $this->circularDependencyCheck);
        }
        $this->circularDependencyCheck[] = $id;
        $instance = $this->classContainer[$className] ??= self::resolveClassDependencies($className);
        array_pop($this->circularDependencyCheck);

        return $instance;
    }

    public function has(string $id): bool
    {
        $className = $this->getClass($id);

        $resolvedClass = $this->classContainer[$className];
        if ($resolvedClass) {
            return true;
        }

        try {
            $this->getClassReflection($id);
        } catch (ContainerExceptionInterface $e) {
            return false;
        }

        return true;
    }

    private function getClass(string $id): string
    {
        return $this->aliases[$id] ?? $id;
    }

    private function getClassReflection(string $class): \ReflectionClass
    {
        try {
            $classReflection = new \ReflectionClass($class);
        } catch (\ReflectionException $e) {
            throw new NotFoundException($class, $e);
        }
        if ($classReflection->isInterface()) {
            throw new UndefinedImplementationException($class);
        }

        return $classReflection;
    }

    public function getParameter(string $parameter): mixed
    {
        if (!$this->hasParameter($parameter)) {
            throw new UndefinedParameterException($parameter);
        }

        return $this->parameterContainer[$parameter];
    }

    private function resolveClassDependencies(string $class): mixed
    {
        $classReflection = $this->getClassReflection($class);

        $constructor = $classReflection->getConstructor();
        if (null === $constructor) {
            return new $class();
        }
        $constructorArgs = [];
        foreach ($constructor->getParameters() as $parameter) {
            $constructorArgs[$parameter->getName()] = $this->resolveReflectionParam($parameter);
        }
        return new $class(...$constructorArgs);
    }

    private function hasParameter(string $parameterName)
    {
        return array_key_exists($parameterName, $this->parameterContainer);
    }

    private function resolveReflectionParam(\ReflectionParameter $parameter): mixed
    {
        if (null === $type = $parameter->getType()) {
            throw new MissingTypeException($parameter->getDeclaringClass()->getName(), $parameter->getName());
        }

        if ($type->isBuiltin()) {
            if (!self::hasParameter($parameter->getName())) {
                throw new UndefinedParameterException($parameter->getName(), $parameter->getDeclaringClass()->getName());
            }

            return $this->parameterContainer[$parameter->getName()];
        }

        return $this->get($type->getName());
    }

    public function setParameter(string $name, array|bool|string|int|float|\UnitEnum|null $value): void
    {
        $this->parameterContainer[$name] = $value;
    }

    public function setImplementation(string $interface, string $implementation): void
    {
        $this->aliases[$interface] = $implementation;
    }

    public function inject(callable $callable): mixed
    {
        if (is_object($callable) && method_exists($callable, '__invoke')) {
            $reflection = new \ReflectionMethod($callable, '__invoke');
        } else if (is_string($callable) && str_contains($callable, '::')) {
            $reflection = new \ReflectionMethod($callable);
        } else {
            $reflection = new \ReflectionFunction($callable);
        }

        $callableArgs = [];
        foreach ($reflection->getParameters() as $parameter) {
            $callableArgs[$parameter->getName()] = self::resolveReflectionParam($parameter);
        }

        return ($callable)(...$callableArgs);
    }
}
