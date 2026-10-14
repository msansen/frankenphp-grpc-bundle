<?php

declare(strict_types=1);

namespace FrankenPhpGrpcBundle\Tests\Unit;

use FrankenPhpGrpcBundle\Dispatcher\GrpcDispatcher;
use FrankenPhpGrpcBundle\Exception\GrpcHandlerException;
use FrankenPhpGrpcBundle\Exception\GrpcMethodNotFoundException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

final class GrpcDispatcherTest extends TestCase
{
    public function testInvokesCallableWithDataOnly(): void
    {
        $handler = new class {
            public function __invoke(array $data): array
            {
                return ['got' => $data];
            }
        };
        $dispatcher = new GrpcDispatcher($this->locator(['/_/Echo' => $handler]), ['/_/Echo' => '__invoke']);
        self::assertSame(['got' => ['k' => 0]], $dispatcher->handle(['method' => '/_/Echo', 'data' => ['k' => 0]]));
    }

    public function testMissingMethod(): void
    {
        $dispatcher = new GrpcDispatcher($this->locator([]), []);
        $this->expectException(GrpcMethodNotFoundException::class);
        $dispatcher->handle(['method' => '/_/Missing', 'data' => []]);
    }

    public function testInvalidReturnType(): void
    {
        $dispatcher = new GrpcDispatcher(
            $this->locator(['/_/Echo' => new class {
                public function __invoke(array $data): string
                {
                    return 'no';
                }
            }]),
            ['/_/Echo' => '__invoke'],
        );
        $this->expectException(GrpcHandlerException::class);
        $dispatcher->handle(['method' => '/_/Echo', 'data' => []]);
    }

    public function testMissingData(): void
    {
        $dispatcher = new GrpcDispatcher($this->locator([]), []);
        $this->expectException(\InvalidArgumentException::class);
        $dispatcher->handle(['method' => '/_/Echo']);
    }

    /** @param array<string, object> $services */
    private function locator(array $services): ContainerInterface
    {
        return new class($services) implements ContainerInterface {
            public function __construct(private array $services)
            {
            }

            public function get(string $id)
            {
                return $this->services[$id] ?? throw new \LogicException('Unknown service');
            }

            public function has(string $id): bool
            {
                return \array_key_exists($id, $this->services);
            }
        };
    }
}
