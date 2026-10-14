<?php

declare(strict_types=1);

namespace FrankenPhpGrpcBundle\Bridge;

use FrankenPhpGrpcBundle\Dispatcher\GrpcDispatcher;
use FrankenPhpGrpcBundle\Error\GrpcExceptionMapper;
use FrankenPhpGrpcBundle\Exception\GrpcHandlerException;
use Symfony\Contracts\Service\ResetInterface;

final class GrpcBridge
{
    public const BRIDGE_ID = '__frankenphp_grpc';
    public const BRIDGE_VERSION = 1;

    public function __construct(
        private readonly GrpcDispatcher $dispatcher,
        private readonly GrpcExceptionMapper $mapper,
        private readonly ?ResetInterface $servicesResetter = null,
    ) {
    }

    public function handle(array $request): array
    {
        try {
            $envelope = $request[self::BRIDGE_ID] ?? null;

            if (!is_array($envelope) || ($envelope['version'] ?? null) !== 1) {
                throw new GrpcHandlerException('Invalid gRPC request envelope or protocol version.');
            }

            $method = $envelope['method'] ?? null;
            $payload = $envelope['payload'] ?? null;

            if (!is_string($method) || '' === $method || !is_array($payload)) {
                throw new GrpcHandlerException('Invalid gRPC request method or payload.');
            }

            $response = $this->dispatcher->handle([
                'method' => $method,
                'data' => $payload,
            ]);

            return $this->wrap([
                'ok' => true,
                'payload' => $response,
            ]);
        } catch (\Throwable $exception) {
            $error = $this->mapper->map($exception);

            return $this->wrap([
                'ok' => false,
                'error' => [
                    'code' => $error->status->value,
                    'message' => $error->message,
                ],
            ]);
        } finally {
            // A failed reset must stop the worker instead of leaking state to the next call.
            $this->servicesResetter?->reset();
        }
    }

    private function wrap(array $result): array
    {
        return [
            self::BRIDGE_ID => [
                'version' => self::BRIDGE_VERSION,
                ...$result,
            ],
        ];
    }
}
