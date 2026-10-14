<?php

declare(strict_types=1);

namespace FrankenPhpGrpcBundle\Tests\Unit;

use FrankenPhpGrpcBundle\DependencyInjection\Compiler\GrpcHandlerPass;
use FrankenPhpGrpcBundle\DependencyInjection\ServiceIds;
use FrankenPhpGrpcBundle\Dispatcher\GrpcDispatcher;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

final class GrpcHandlerPassTest extends TestCase
{
    public function testHigherPriorityReplacesExistingHandler(): void
    {
        $container = $this->container();
        $container->register('default.handler', \stdClass::class)->addTag(GrpcHandlerPass::TAG, [
            'path' => '/test.Echo/Hello', 'priority' => 0,
        ]);
        $container->register('override.handler', \stdClass::class)->addTag(GrpcHandlerPass::TAG, [
            'path' => '/test.Echo/Hello', 'priority' => 100,
        ]);
        (new GrpcHandlerPass())->process($container);
        $args = $container->getDefinition(ServiceIds::DISPATCHER)->getArguments();
        self::assertInstanceOf(Reference::class, $args[0]);
        self::assertTrue($container->hasDefinition((string) $args[0]));
        self::assertSame(['/test.Echo/Hello' => '__invoke'], $args[1]);
    }

    public function testDuplicateRouteAtSamePriorityFails(): void
    {
        $container = $this->container();
        $container->register('handler.a', \stdClass::class)->addTag(GrpcHandlerPass::TAG, ['path' => '/test.Echo/Hello']);
        $container->register('handler.b', \stdClass::class)->addTag(GrpcHandlerPass::TAG, ['path' => '/test.Echo/Hello']);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Duplicate gRPC handler');
        (new GrpcHandlerPass())->process($container);
    }

    public function testInvalidGrpcPathFails(): void
    {
        $container = $this->container();
        $container->register('handler.a', \stdClass::class)->addTag(GrpcHandlerPass::TAG, ['path' => 'test.Echo/Hello']);
        $this->expectException(InvalidArgumentException::class);
        (new GrpcHandlerPass())->process($container);
    }

    private function container(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->register(ServiceIds::DISPATCHER, GrpcDispatcher::class)->setArguments([[], []]);

        return $container;
    }
}
