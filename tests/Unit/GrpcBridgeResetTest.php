<?php

declare(strict_types=1);

namespace FrankenPhpGrpcBundle\Tests\Unit;

use FrankenPhpGrpcBundle\Bridge\GrpcBridge;
use FrankenPhpGrpcBundle\Dispatcher\GrpcDispatcher;
use FrankenPhpGrpcBundle\Error\GrpcExceptionMapper;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ResetInterface;

final class GrpcBridgeResetTest extends TestCase
{
    public function testInvalidEnvelopeStillResetsServices(): void
    {
        $resetter = $this->createMock(ResetInterface::class);
        $resetter->expects(self::once())->method('reset');

        $response = $this->bridge($resetter)->handle([]);

        self::assertFalse($response[GrpcBridge::BRIDGE_ID]['ok']);
    }

    public function testBridgeWorksWithoutResetter(): void
    {
        $response = $this->bridge()->handle([]);

        self::assertSame(13, $response[GrpcBridge::BRIDGE_ID]['error']['code']);
    }

    public function testResetFailurePropagates(): void
    {
        $failure = new \RuntimeException('Reset failed.');
        $resetter = $this->createMock(ResetInterface::class);
        $resetter->expects(self::once())->method('reset')->willThrowException($failure);

        $this->expectExceptionObject($failure);

        $this->bridge($resetter)->handle([]);
    }

    private function bridge(?ResetInterface $resetter = null): GrpcBridge
    {
        return new GrpcBridge(
            new GrpcDispatcher($this->createStub(ContainerInterface::class), []),
            new GrpcExceptionMapper(),
            $resetter,
        );
    }
}
