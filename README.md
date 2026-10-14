# FrankenPHP gRPC Bundle

Expose Symfony services as gRPC handlers with [FrankenPHP gRPC](https://github.com/dunglas/frankenphp-grpc).
Declare RPC routes with PHP attributes, inject application services into your
handlers, and map exceptions to gRPC statuses.

- Routes on invokable classes or individual methods.
- Route validation and priority-based overrides during container compilation.
- Lazy handler resolution through Symfony's service container.
- Configurable service reset between calls.
- Array-based payloads without a PHP gRPC or Protobuf extension.

## Requirements

- PHP 8.2 or later, subject to the requirements of your Symfony version.
- Symfony 6.4, 7.4 or 8.1.
- A FrankenPHP binary with the gRPC extension and generated Go services implementing
  the bundle's [bridge protocol](docs/bridge-protocol.md).

The bundle provides the Symfony integration. Build and configure the FrankenPHP
gRPC extension and Go services separately.

## Installation

```bash
composer require msansen/frankenphp-grpc-bundle
```

Register the bundle in `config/bundles.php`:

```php
return [
    // Other bundles...
    FrankenPhpGrpcBundle\FrankenPhpGrpcBundle::class => ['all' => true],
];
```

Enable `autowire` and `autoconfigure` for your application services, as in
[`examples/config-services.yaml`](examples/config-services.yaml).

## Create a handler

Create `src/Grpc/Handler/EchoHandler.php`:

```php
<?php

namespace App\Grpc\Handler;

use FrankenPhpGrpcBundle\Attribute\GrpcHandler;

#[GrpcHandler('/example.v1.EchoService/Echo')]
final class EchoHandler
{
    public function __invoke(array $request): array
    {
        return ['message' => (string) ($request['message'] ?? '')];
    }
}
```

A handler receives the RPC payload and must return an array. Constructor
dependencies are injected by Symfony. No handler interface is required.
Routes use the case-sensitive path `/<package>.<Service>/<Method>`.

To group RPCs in one service, apply `#[GrpcHandler]` to each method. See
[`examples/Handler/MultipleMethodsHandler.php`](examples/Handler/MultipleMethodsHandler.php).

Existing services can also be registered with a tag:

```yaml
services:
  App\Grpc\Handler\LegacyHandler:
    tags:
      - { name: frankenphp_grpc.handler, path: '/example.v1.EchoService/Legacy', method: '__invoke' }
```

To replace a handler, register the same route with a higher `priority`:

```php
#[GrpcHandler('/example.v1.EchoService/Echo', priority: 100)]
```

Handlers sharing a route and priority cause a container compilation error.

## Configure the worker

Copy [`examples/grpc-worker.php`](examples/grpc-worker.php) to your Symfony
application root and adapt [`examples/Caddyfile`](examples/Caddyfile) to its path.
The worker boots the kernel once and delegates each call to `GrpcBridge`.

The Go services must send the versioned request envelope and decode the bridge's
success and error responses. See the [bridge protocol](docs/bridge-protocol.md)
for the complete contract.

The worker accepts `APP_ENV`, `APP_DEBUG` and `MAX_REQUESTS`.
`MAX_REQUESTS=0`, the default, processes requests without a fixed limit;
a positive value stops the worker after that many requests.

## Error handling

Throw `GrpcStatusException` to return an explicit gRPC error:

```php
use FrankenPhpGrpcBundle\Exception\GrpcStatusException;

throw GrpcStatusException::invalidArgument('The message must be a string.');
```

Helpers are available for `invalidArgument()`, `notFound()` and `unavailable()`.
For other statuses, pass a `GrpcStatusCode` to the exception constructor.
The exception message is returned to the client.

To translate application exceptions, implement
`FrankenPhpGrpcBundle\Error\GrpcExceptionMapperInterface`. Return a `GrpcError`
when the exception is handled, or `null` to try the next mapper. See the
[mapper](examples/Error/InvalidMessageMapper.php),
[application exception](examples/Exception/InvalidMessage.php) and
[handler](examples/Handler/EchoHandler.php) examples.
Copy these directories into your application's `src/Grpc/` directory.

Autoconfigured mappers receive the `frankenphp_grpc.exception_mapper` tag.
Higher tag priorities run first; the first mapper returning an error wins.
See [`examples/config-services.yaml`](examples/config-services.yaml) for manual registration.

Explicit gRPC status exceptions and unknown routes are handled before custom mappers:

| Error | Default response |
| --- | --- |
| `GrpcStatusException` | Its status and message |
| Unknown route | `UNIMPLEMENTED` (12) |
| Other unhandled exception, including an invalid bridge envelope | `INTERNAL` (13), `Internal server error` |

Unexpected exception details are logged when a `logger` service is available.

## Configuration

The bundle resets initialized services tagged with `kernel.reset` after each
bridge invocation, including failed requests. In a FrameworkBundle application,
autoconfigured services implementing `Symfony\Contracts\Service\ResetInterface`
receive this tag automatically. Other services can declare it explicitly:

```yaml
services:
  App\Grpc\RequestContext:
    tags:
      - { name: kernel.reset, method: reset }
```

To disable reset, add `config/packages/franken_php_grpc.yaml`:

```yaml
franken_php_grpc:
  reset_services: false # Default: true
```

Reset runs after the handler and error mapping, before the bridge returns its
response. It uses Symfony's `services_resetter`; if that service is absent, reset
is skipped. A reset failure propagates and stops the example worker.
Direct dispatcher calls do not trigger reset, and untagged services retain their state.

## Services

Public service IDs and class aliases resolve to the same instances.
Use class types for constructor injection, or retrieve services by ID in a worker.

| Service ID | Class alias |
| --- | --- |
| `frankenphp_grpc.bridge` | `FrankenPhpGrpcBundle\Bridge\GrpcBridge` |
| `frankenphp_grpc.dispatcher` | `FrankenPhpGrpcBundle\Dispatcher\GrpcDispatcher` |
| `frankenphp_grpc.exception_mapper` | `FrankenPhpGrpcBundle\Error\GrpcExceptionMapper` |

`FrankenPhpGrpcBundle\DependencyInjection\ServiceIds` exposes the corresponding
`BRIDGE`, `DISPATCHER` and `EXCEPTION_MAPPER` constants.
Use the bridge at the Go/PHP boundary to apply response wrapping, error mapping
and service reset.

## Contributing

The development commands use Docker:

```bash
make install
make lint  # Apply the Symfony coding standards
make test  # Run PHPUnit
make check # Run lint and tests
```

CI checks coding standards without modifying files and runs tests across the
supported PHP/Symfony combinations.

## License

[MIT](LICENSE).
