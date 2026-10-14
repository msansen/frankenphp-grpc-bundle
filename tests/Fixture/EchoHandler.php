<?php

declare(strict_types=1);

namespace FrankenPhpGrpcBundle\Tests\Fixture;

use FrankenPhpGrpcBundle\Attribute\GrpcHandler;

#[GrpcHandler('/example.v1.EchoService/Echo')]
final class EchoHandler
{
    public function __invoke(array $data): array
    {
        return ['message' => 'Echo: '.($data['message'] ?? '')];
    }
}
