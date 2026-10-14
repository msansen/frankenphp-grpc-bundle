<?php

declare(strict_types=1);

namespace FrankenPhpGrpcBundle\Error;

use FrankenPhpGrpcBundle\Status\GrpcStatusCode;

final readonly class GrpcError
{
    public function __construct(
        public GrpcStatusCode $status,
        public string $message,
    ) {
        if (GrpcStatusCode::OK === $status) {
            throw new \InvalidArgumentException('OK is not an error status.');
        }
    }
}
