<?php

declare(strict_types=1);

namespace FrankenPhpGrpcBundle\DependencyInjection\Compiler;

use FrankenPhpGrpcBundle\DependencyInjection\ServiceIds;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

final class GrpcHandlerPass implements CompilerPassInterface
{
    public const TAG = 'frankenphp_grpc.handler';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(ServiceIds::DISPATCHER)) {
            return;
        }

        /** @var array<string, array{service: string, method: string, priority: int}> $routes */
        $routes = [];

        foreach ($container->findTaggedServiceIds(self::TAG) as $id => $tags) {
            foreach ($tags as $tag) {
                $path = $tag['path'] ?? null;
                $method = $tag['method'] ?? '__invoke';
                $priority = $tag['priority'] ?? 0;

                if (!\is_string($path) || !self::isValidPath($path)) {
                    throw new InvalidArgumentException(\sprintf('Invalid gRPC path for service "%s": expected "/package.Service/Method".', $id));
                }
                if (!\is_string($method) || !\preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $method)) {
                    throw new InvalidArgumentException(\sprintf('Invalid handler method for "%s" (%s).', $path, $id));
                }
                if (!\is_int($priority)) {
                    throw new InvalidArgumentException(\sprintf('Invalid priority for "%s" (%s): expected integer.', $path, $id));
                }

                $candidate = ['service' => $id, 'method' => $method, 'priority' => $priority];
                if (!isset($routes[$path])) {
                    $routes[$path] = $candidate;
                    continue;
                }

                $existing = $routes[$path];
                if ($priority === $existing['priority']) {
                    throw new InvalidArgumentException(\sprintf('Duplicate gRPC handler for "%s": "%s::%s" and "%s::%s" (same priority %d).', $path, $existing['service'], $existing['method'], $id, $method, $priority));
                }

                if ($priority > $existing['priority']) {
                    $routes[$path] = $candidate;
                }
            }
        }

        \ksort($routes);
        $references = [];
        $methods = [];
        foreach ($routes as $path => $route) {
            $references[$path] = new Reference($route['service']);
            $methods[$path] = $route['method'];
        }

        $container
            ->getDefinition(ServiceIds::DISPATCHER)
            ->setArguments([
                ServiceLocatorTagPass::register($container, $references),
                $methods,
            ])
        ;
    }

    private static function isValidPath(string $path): bool
    {
        return 1 === \preg_match('~^/[A-Za-z_][A-Za-z0-9_]*(?:\.[A-Za-z_][A-Za-z0-9_]*)*/[A-Za-z_][A-Za-z0-9_]*$~D', $path);
    }
}
