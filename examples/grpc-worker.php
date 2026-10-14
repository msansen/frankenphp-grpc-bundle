<?php

declare(strict_types=1);

// Copy to your Symfony APPLICATION ROOT as grpc-worker.php.
// This is the gRPC worker used by the FrankenPHP gRPC Caddy directive,
// NOT the normal HTTP worker or Symfony public/index.php.
require __DIR__.'/vendor/autoload.php';

use FrankenPhpGrpcBundle\DependencyInjection\ServiceIds;

$kernel = new App\Kernel($_SERVER['APP_ENV'] ?? 'prod', (bool) (int) ($_SERVER['APP_DEBUG'] ?? 0));
$kernel->boot();
$bridge = $kernel->getContainer()->get(ServiceIds::BRIDGE);
// GrpcBridge::class is a public alias for the same service.

$handler = static fn (array $envelope): array => $bridge->handle($envelope);

$maxRequests = (int) ($_SERVER['MAX_REQUESTS'] ?? 0);
try {
    for ($count = 0; 0 === $maxRequests || $count < $maxRequests; ++$count) {
        if (!\frankenphp_handle_request($handler)) {
            break;
        }
        \gc_collect_cycles();
    }
} finally {
    $kernel->shutdown();
}
