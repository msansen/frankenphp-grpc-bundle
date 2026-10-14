<?php

declare(strict_types=1);

namespace FrankenPhpGrpcBundle\Tests\Integration;

use FrankenPhpGrpcBundle\Bridge\GrpcBridge;
use FrankenPhpGrpcBundle\DependencyInjection\ServiceIds;
use FrankenPhpGrpcBundle\Dispatcher\GrpcDispatcher;
use FrankenPhpGrpcBundle\Exception\GrpcMethodNotFoundException;
use FrankenPhpGrpcBundle\Tests\Fixture\TestKernel;
use PHPUnit\Framework\TestCase;

final class BundleIntegrationTest extends TestCase
{
    private TestKernel $kernel;
    private GrpcDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->kernel = new TestKernel('test', true);
        $this->kernel->boot();
        $this->dispatcher = $this->kernel->getContainer()->get(GrpcDispatcher::class);
    }

    protected function tearDown(): void
    {
        $this->kernel->shutdown();
    }

    public function testNamedServiceAndClassAliasResolveToSameDispatcher(): void
    {
        self::assertSame(
            $this->kernel->getContainer()->get(ServiceIds::DISPATCHER),
            $this->kernel->getContainer()->get(GrpcDispatcher::class),
        );
    }

    public function testNamedServiceAndClassAliasResolveToSameBridge(): void
    {
        self::assertSame(
            $this->kernel->getContainer()->get(ServiceIds::BRIDGE),
            $this->kernel->getContainer()->get(GrpcBridge::class),
        );
    }

    public function testBridgeCanDispatchThroughNamedServices(): void
    {
        $bridge = $this->kernel->getContainer()->get(GrpcBridge::class);
        self::assertSame(
            [
                '__frankenphp_grpc' => [
                    'version' => 1,
                    'ok' => true,
                    'payload' => ['message' => 'Echo: hi'],
                ],
            ],
            $bridge->handle([
                '__frankenphp_grpc' => [
                    'version' => 1,
                    'method' => '/example.v1.EchoService/Echo',
                    'payload' => ['message' => 'hi'],
                ],
            ]),
        );
    }

    public function testClassAttributeWithAutoconfiguredPrivateService(): void
    {
        self::assertSame(
            ['message' => 'Echo: hi'],
            $this->dispatcher->handle(['method' => '/example.v1.EchoService/Echo', 'data' => ['message' => 'hi']]),
        );
    }

    public function testMethodAttributesOnSameService(): void
    {
        self::assertSame(
            ['message' => 'HI'],
            $this->dispatcher->handle(['method' => '/example.v1.EchoService/Uppercase', 'data' => ['message' => 'hi']]),
        );
        self::assertSame(
            [],
            $this->dispatcher->handle(['method' => '/example.v1.EchoService/Empty', 'data' => []]),
        );
    }

    public function testUnknownRoute(): void
    {
        $this->expectException(GrpcMethodNotFoundException::class);
        $this->dispatcher->handle(['method' => '/example.v1.EchoService/Missing', 'data' => []]);
    }

    public function testMalformedEnvelope(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->dispatcher->handle(['method' => '/example.v1.EchoService/Echo', 'data' => 'invalid']);
    }
}
