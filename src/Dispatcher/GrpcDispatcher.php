<?php

declare(strict_types=1);

namespace FrankenPhpGrpcBundle\Dispatcher;

use FrankenPhpGrpcBundle\Exception\GrpcHandlerException;
use FrankenPhpGrpcBundle\Exception\GrpcMethodNotFoundException;
use Psr\Container\ContainerInterface;

/**
 * Receives Go->PHP envelopes and routes to Symfony handler services.
 * Input:  ['method' => '/package.Service/Method', 'data' => [...]]
 * Output: the handler's array, unchanged.
 */
final class GrpcDispatcher
{
    /** @param array<string, string> $methods Path => PHP method to invoke */
    public function __construct(
        private readonly ContainerInterface $handlers,
        private readonly array $methods,
    ) {
    }

    /** @param array<string, mixed> $request
     * @return array<mixed>
     */
    public function handle(array $request): array
    {
        $path = $request['method'] ?? null;
        if (!\is_string($path) || '' === $path) {
            throw new \InvalidArgumentException('Missing or invalid gRPC envelope key "method".');
        }
        if (!\array_key_exists('data', $request) || !\is_array($request['data'])) {
            throw new \InvalidArgumentException('Missing or invalid gRPC envelope key "data": expected array.');
        }

        if (!$this->handlers->has($path)) {
            throw new GrpcMethodNotFoundException($path);
        }

        $service = $this->handlers->get($path);
        $method = $this->methods[$path] ?? '__invoke';
        if (!\is_callable([$service, $method])) {
            throw new GrpcHandlerException(\sprintf('gRPC handler for "%s" is not callable (method "%s").', $path, $method));
        }

        $response = $service->{$method}($request['data']);
        if (!\is_array($response)) {
            throw new GrpcHandlerException(\sprintf('gRPC handler for "%s" must return an array, got "%s".', $path, \get_debug_type($response)));
        }

        return $response;
    }
}
