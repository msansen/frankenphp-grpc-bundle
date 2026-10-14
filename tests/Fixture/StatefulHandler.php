<?php

declare(strict_types=1);

namespace FrankenPhpGrpcBundle\Tests\Fixture;

use FrankenPhpGrpcBundle\Attribute\GrpcHandler;
use FrankenPhpGrpcBundle\Exception\GrpcStatusException;

#[GrpcHandler('/example.v1.Stateful/Count')]
final class StatefulHandler
{
    private int $count = 0;

    public function __invoke(array $data): array
    {
        ++$this->count;
        if ($data['fail'] ?? false) {
            throw GrpcStatusException::invalidArgument('Invalid request.');
        }

        return ['count' => $this->count];
    }

    public function reset(): void
    {
        $this->count = 0;
    }
}
