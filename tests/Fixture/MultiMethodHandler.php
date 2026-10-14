<?php

declare(strict_types=1);

namespace FrankenPhpGrpcBundle\Tests\Fixture;

use FrankenPhpGrpcBundle\Attribute\GrpcHandler;

final class MultiMethodHandler
{
    #[GrpcHandler('/example.v1.EchoService/Uppercase')]
    public function upper(array $data): array
    {
        return ['message' => \strtoupper((string) ($data['message'] ?? ''))];
    }

    #[GrpcHandler('/example.v1.EchoService/Empty')]
    public function empty(array $data): array
    {
        return [];
    }
}
