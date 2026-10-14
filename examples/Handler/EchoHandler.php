<?php

declare(strict_types=1);

namespace App\Grpc\Handler;

use App\Grpc\Exception\InvalidMessage;
use FrankenPhpGrpcBundle\Attribute\GrpcHandler;

#[GrpcHandler('/example.v1.EchoService/Echo')]
final class EchoHandler
{
    public function __invoke(array $request): array
    {
        $message = $request['message'] ?? null;
        if (!\is_string($message) || '' === \trim($message)) {
            throw new InvalidMessage();
        }

        return ['message' => $message];
    }
}
