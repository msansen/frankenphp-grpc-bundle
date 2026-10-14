<?php

declare(strict_types=1);

namespace FrankenPhpGrpcBundle\Error;

use FrankenPhpGrpcBundle\Exception\GrpcMethodNotFoundException;
use FrankenPhpGrpcBundle\Exception\GrpcStatusException;
use FrankenPhpGrpcBundle\Status\GrpcStatusCode;
use Psr\Log\LoggerInterface;

final class GrpcExceptionMapper
{
    /** @param iterable<GrpcExceptionMapperInterface> $customMappers */
    public function __construct(
        private readonly iterable $customMappers = [],
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    public function map(\Throwable $exception): GrpcError
    {
        // Explicit gRPC errors
        if ($exception instanceof GrpcStatusException) {
            return new GrpcError($exception->status, $exception->getMessage());
        }

        // Known bundle exceptions
        if ($exception instanceof GrpcMethodNotFoundException) {
            return new GrpcError(GrpcStatusCode::UNIMPLEMENTED, $exception->getMessage());
        }

        // Application-specific mappings
        foreach ($this->customMappers as $mapper) {
            $error = $mapper->map($exception);

            if (null !== $error) {
                return $error;
            }
        }

        // Unexpected exception: log details, but never expose them.
        $this->logger?->error('Unhandled exception in gRPC handler', ['exception' => $exception]);

        return new GrpcError(GrpcStatusCode::INTERNAL, 'Internal server error');
    }
}
