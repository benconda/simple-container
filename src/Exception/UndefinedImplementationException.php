<?php

declare(strict_types=1);

namespace Benconda\SimpleContainer\Exception;

use Psr\Container\ContainerExceptionInterface;

final class UndefinedImplementationException extends \Exception implements ContainerExceptionInterface
{

    public function __construct(string $class, ?\Throwable $previous = null)
    {
        $message = sprintf("Please specify an implementation for interface %s", $class);

        parent::__construct($message, previous: $previous);
    }
}