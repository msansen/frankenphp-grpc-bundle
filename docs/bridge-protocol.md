# Go/PHP bridge protocol

The worker calls `GrpcBridge::handle()` with a versioned request:

```php
[
    '__frankenphp_grpc' => [
        'version' => 1,
        'method' => '/example.v1.EchoService/Echo',
        'payload' => ['message' => 'Hello'],
    ],
]
```

The bridge translates this to the dispatcher's internal `method` / `data`
envelope. The handler receives only the payload and must return an array.
The bridge wraps that array in a success response:

```php
[
    '__frankenphp_grpc' => [
        'version' => 1,
        'ok' => true,
        'payload' => ['message' => 'Hello'],
    ],
]
```

On failure, the bridge returns a numeric gRPC status and a client-facing message:

```php
[
    '__frankenphp_grpc' => [
        'version' => 1,
        'ok' => false,
        'error' => ['code' => 3, 'message' => 'The message must be a non-empty string.'],
    ],
]
```

The Go integration must send this request envelope, unwrap successful payloads,
and translate errors into gRPC statuses. The legacy `method` / `data` envelope
alone is not accepted by `GrpcBridge`. Configure the Go integration to match this
contract; a generator's `envelope` option alone does not guarantee compatibility.

[Back to the README](../README.md)
