<?php

declare(strict_types=1);

namespace FrankenPhpGrpcBundle\Exception;

use FrankenPhpGrpcBundle\Status\GrpcStatusCode;

final class GrpcStatusException extends \RuntimeException
{
    public function __construct(
        public readonly GrpcStatusCode $status,
        string $message,
        ?\Throwable $previous = null,
    ) {
        if (GrpcStatusCode::OK === $status) {
            throw new \InvalidArgumentException('OK is not an error status.');
        }

        parent::__construct($message, $status->value, $previous);
    }

    public static function notFound(string $message): self
    {
        return new self(GrpcStatusCode::NOT_FOUND, $message);
    }

    public static function invalidArgument(string $message): self
    {
        return new self(GrpcStatusCode::INVALID_ARGUMENT, $message);
    }

    public static function unavailable(string $message): self
    {
        return new self(GrpcStatusCode::UNAVAILABLE, $message);
    }
}
