<?php

declare(strict_types=1);

namespace Benconda\SimpleContainer\Exception;

use Psr\Container\ContainerExceptionInterface;

final class MissingTypeException extends \Exception implements ContainerExceptionInterface
{
    public function __construct(public readonly string $class, public readonly string $parameterName, ?\Throwable $previous = null)
    {
        $message = sprintf('Missing type on constructor parameter %s in class %s',
            $parameterName,
            $class,
        );

        parent::__construct($message, previous: $previous);
    }
}