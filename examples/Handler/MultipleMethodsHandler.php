<?php

declare(strict_types=1);

namespace App\Grpc\Handler;

use FrankenPhpGrpcBundle\Attribute\GrpcHandler;
use FrankenPhpGrpcBundle\Exception\GrpcStatusException;

final class MultipleMethodsHandler
{
    #[GrpcHandler('/example.v1.EchoService/Uppercase')]
    public function upper(array $request): array
    {
        $message = $request['message'] ?? null;
        if (!\is_string($message)) {
            throw GrpcStatusException::invalidArgument('The message must be a string.');
        }

        return ['message' => \strtoupper($message)];
    }

    #[GrpcHandler('/example.v1.EchoService/Lowercase')]
    public function lower(array $request): array
    {
        return ['message' => \strtolower((string) ($request['message'] ?? ''))];
    }
}
