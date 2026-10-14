<?php

declare(strict_types=1);

namespace FrankenPhpGrpcBundle\Exception;

final class GrpcMethodNotFoundException extends GrpcHandlerException
{
    public function __construct(string $path)
    {
        parent::__construct(\sprintf('No Symfony gRPC handler registered for "%s".', $path));
    }
}
