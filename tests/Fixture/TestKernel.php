<?php

declare(strict_types=1);

namespace FrankenPhpGrpcBundle\Tests\Fixture;

use FrankenPhpGrpcBundle\FrankenPhpGrpcBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\Argument\IteratorArgument;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\ResettableServicePass;
use Symfony\Component\HttpKernel\DependencyInjection\ServicesResetter;
use Symfony\Component\HttpKernel\Kernel;

final class TestKernel extends Kernel
{
    public function __construct(string $environment, bool $debug, private readonly bool $resetServices = true)
    {
        parent::__construct($environment, $debug);
    }

    public function registerBundles(): iterable
    {
        yield new FrankenPhpGrpcBundle();
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $resetServices = $this->resetServices;
        $loader->load(static function (ContainerBuilder $container) use ($resetServices): void {
            $container->loadFromExtension('franken_php_grpc', $resetServices ? [] : ['reset_services' => false]);
            // Supply the reset infrastructure normally registered by FrameworkBundle.
            $container->register('services_resetter', ServicesResetter::class)
                ->setArguments([new IteratorArgument([]), []]);
            $container->addCompilerPass(new ResettableServicePass(), PassConfig::TYPE_BEFORE_REMOVING);
            $container->register(StatefulHandler::class, StatefulHandler::class)
                ->setAutoconfigured(true)
                ->addTag('kernel.reset', ['method' => 'reset']);
            $container->register(EchoHandler::class, EchoHandler::class)
                ->setAutoconfigured(true)
                ->setAutowired(true);
            $container->register(MultiMethodHandler::class, MultiMethodHandler::class)
                ->setAutoconfigured(true)
                ->setAutowired(true);
        });
    }

    public function getCacheDir(): string
    {
        return \sys_get_temp_dir().'/frankenphp-grpc-bundle-test-'.\PHP_VERSION_ID.'-'.$this->environment;
    }

    public function getLogDir(): string
    {
        return $this->getCacheDir().'/log';
    }
}
