<?php

declare(strict_types=1);

namespace Benconda\SimpleContainer\Exception;

use Psr\Container\ContainerExceptionInterface;

final class UndefinedParameterException extends \Exception implements ContainerExceptionInterface
{

    public function __construct(string $parameterName, ?string $fromClassConstructor = null, ?\Throwable $previous = null)
    {
        $message = sprintf('Parameter %s is not defined', $parameterName);
        if (null !== $fromClassConstructor) {
            $message .= sprintf(' used in %s constructor', $fromClassConstructor);
        }

        parent::__construct($message, previous: $previous);
    }
}