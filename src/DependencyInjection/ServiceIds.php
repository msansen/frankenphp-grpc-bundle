<?php

declare(strict_types=1);

namespace FrankenPhpGrpcBundle\DependencyInjection;

final class ServiceIds
{
    public const BRIDGE = 'frankenphp_grpc.bridge';
    public const DISPATCHER = 'frankenphp_grpc.dispatcher';
    public const EXCEPTION_MAPPER = 'frankenphp_grpc.exception_mapper';

    private function __construct()
    {
    }
}
