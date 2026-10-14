<?php

declare(strict_types=1);

namespace FrankenPhpGrpcBundle\Attribute;

/**
 * Declares the fully-qualified gRPC method served by an invokable class or a method.
 * Example: #[GrpcHandler('/helloworld.Greeter/SayHello')].
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
final readonly class GrpcHandler
{
    public function __construct(
        public string $path,
        public int $priority = 0,
        public bool $defaultHandler = false,
    ) {
    }
}
