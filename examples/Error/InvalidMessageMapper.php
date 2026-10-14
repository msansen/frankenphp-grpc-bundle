<?php

declare(strict_types=1);

namespace App\Grpc\Error;

use App\Grpc\Exception\InvalidMessage;
use FrankenPhpGrpcBundle\Error\GrpcError;
use FrankenPhpGrpcBundle\Error\GrpcExceptionMapperInterface;
use FrankenPhpGrpcBundle\Status\GrpcStatusCode;

final class InvalidMessageMapper implements GrpcExceptionMapperInterface
{
    public function map(\Throwable $exception): ?GrpcError
    {
        if (!$exception instanceof InvalidMessage) {
            return null;
        }

        // Only expose a message intended for the gRPC client.
        return new GrpcError(GrpcStatusCode::INVALID_ARGUMENT, 'The message must be a non-empty string.');
    }
}
