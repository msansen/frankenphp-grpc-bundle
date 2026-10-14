<?php

declare(strict_types=1);

namespace FrankenPhpGrpcBundle\Error;

interface GrpcExceptionMapperInterface
{
    // return null if the mapper doesn't handle the exception
    public function map(\Throwable $exception): ?GrpcError;
}
