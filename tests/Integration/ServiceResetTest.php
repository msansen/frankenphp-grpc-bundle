<?php

declare(strict_types=1);

namespace FrankenPhpGrpcBundle\Tests\Integration;

use FrankenPhpGrpcBundle\Bridge\GrpcBridge;
use FrankenPhpGrpcBundle\Tests\Fixture\TestKernel;
use PHPUnit\Framework\TestCase;

final class ServiceResetTest extends TestCase
{
    public function testServicesAreResetBetweenCalls(): void
    {
        $this->assertCounts(true, [1, 1]);
    }

    public function testResetCanBeDisabled(): void
    {
        $this->assertCounts(false, [1, 2]);
    }

    public function testServicesAreResetAfterHandlerFailure(): void
    {
        $kernel = new TestKernel('reset_failure', true);
        try {
            $kernel->boot();
            $bridge = $kernel->getContainer()->get(GrpcBridge::class);
            $response = $bridge->handle($this->request(['fail' => true]));
            self::assertFalse($response[GrpcBridge::BRIDGE_ID]['ok']);
            self::assertSame(3, $response[GrpcBridge::BRIDGE_ID]['error']['code']);
            self::assertSame(1, $bridge->handle($this->request())[GrpcBridge::BRIDGE_ID]['payload']['count']);
        } finally {
            $kernel->shutdown();
        }
    }

    private function assertCounts(bool $resetServices, array $expected): void
    {
        $kernel = new TestKernel($resetServices ? 'reset_enabled' : 'reset_disabled', true, $resetServices);
        try {
            $kernel->boot();
            $bridge = $kernel->getContainer()->get(GrpcBridge::class);
            $counts = [];
            foreach ($expected as $_) {
                $counts[] = $bridge->handle($this->request())[GrpcBridge::BRIDGE_ID]['payload']['count'];
            }
            self::assertSame($expected, $counts);
        } finally {
            $kernel->shutdown();
        }
    }

    private function request(array $payload = []): array
    {
        return [GrpcBridge::BRIDGE_ID => [
            'version' => GrpcBridge::BRIDGE_VERSION,
            'method' => '/example.v1.Stateful/Count',
            'payload' => $payload,
        ]];
    }
}
