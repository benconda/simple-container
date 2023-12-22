<?php

declare(strict_types=1);

namespace Benconda\SimpleContainer\Exception;

use Psr\Container\NotFoundExceptionInterface;

final class NotFoundException extends \Exception implements NotFoundExceptionInterface
{
    public function __construct(string $class, ?\Throwable $previous = null)
    {
        $message = sprintf("Class %s is not found", $class);

        parent::__construct($message, previous: $previous);
    }
}