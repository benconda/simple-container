<?php

declare(strict_types=1);

namespace Benconda\SimpleContainer\Exception;

use Psr\Container\ContainerExceptionInterface;

final class CircularDependencyException extends \Exception implements ContainerExceptionInterface
{
    public function __construct(string $class, array $hierarchy, ?\Throwable $previous = null)
    {
        $message = sprintf('Circular dependency detected for class %s : %s',
            $class,
            implode(' -> ', [...$hierarchy, $class])
        );

        parent::__construct($message, previous: $previous);
    }
}