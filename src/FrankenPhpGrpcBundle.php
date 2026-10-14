<?php

declare(strict_types=1);

namespace FrankenPhpGrpcBundle;

use FrankenPhpGrpcBundle\Attribute\GrpcHandler;
use FrankenPhpGrpcBundle\Bridge\GrpcBridge;
use FrankenPhpGrpcBundle\DependencyInjection\Compiler\GrpcHandlerPass;
use FrankenPhpGrpcBundle\DependencyInjection\ServiceIds;
use FrankenPhpGrpcBundle\Dispatcher\GrpcDispatcher;
use FrankenPhpGrpcBundle\Error\GrpcExceptionMapper;
use FrankenPhpGrpcBundle\Error\GrpcExceptionMapperInterface;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class FrankenPhpGrpcBundle extends AbstractBundle
{
    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->rootNode()
            ->children()
                ->booleanNode('reset_services')
                    ->defaultTrue()
                    ->info('Reset initialized kernel.reset services after each gRPC bridge invocation.')
                ->end()
            ->end()
        ;
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->registerAttributeForAutoconfiguration(
            GrpcHandler::class,
            static function (ChildDefinition $definition, GrpcHandler $attribute, \Reflector $reflector): void {
                $definition->addTag(GrpcHandlerPass::TAG, [
                    'path' => $attribute->path,
                    'method' => $reflector instanceof \ReflectionMethod ? $reflector->getName() : '__invoke',
                    'priority' => $attribute->priority,
                ]);
            },
        );

        $container
            ->registerForAutoconfiguration(GrpcExceptionMapperInterface::class)
            ->addTag('frankenphp_grpc.exception_mapper');

        $container->addCompilerPass(new GrpcHandlerPass(), PassConfig::TYPE_BEFORE_REMOVING);
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $services = $container->services();

        $services
            ->set(ServiceIds::DISPATCHER, GrpcDispatcher::class)
            ->args([[], []])
            ->public()
        ;

        $services
            ->alias(GrpcDispatcher::class, ServiceIds::DISPATCHER)
            ->public()
        ;

        $services
            ->set(ServiceIds::EXCEPTION_MAPPER, GrpcExceptionMapper::class)
            ->args([
                new TaggedIteratorArgument('frankenphp_grpc.exception_mapper'),
                new Reference('logger', ContainerInterface::NULL_ON_INVALID_REFERENCE),
            ])
            ->public()
        ;

        $services
            ->alias(GrpcExceptionMapper::class, ServiceIds::EXCEPTION_MAPPER)
            ->public()
        ;

        $services
            ->set(ServiceIds::BRIDGE, GrpcBridge::class)
            ->args([
                new Reference(GrpcDispatcher::class),
                new Reference(GrpcExceptionMapper::class),
                $config['reset_services'] ? new Reference('services_resetter', ContainerInterface::NULL_ON_INVALID_REFERENCE) : null,
            ])
            ->public()
        ;

        $services
            ->alias(GrpcBridge::class, ServiceIds::BRIDGE)
            ->public()
        ;
    }
}
