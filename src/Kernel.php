<?php

declare(strict_types=1);

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $configDir = $this->getProjectDir() . '/config';

        if (is_dir($configDir . '/packages')) {
            $loader->load($configDir . '/packages/*.yaml', 'glob');
        }

        $envPackagesDir = $configDir . '/packages/' . $this->environment;
        if (is_dir($envPackagesDir)) {
            $loader->load($envPackagesDir . '/*.yaml', 'glob');
        }

        $loader->load($configDir . '/services.yaml');

        $envServices = $configDir . '/services_' . $this->environment . '.yaml';
        if (is_file($envServices)) {
            $loader->load($envServices);
        }
    }

    protected function configureRoutes(RoutingConfigurator $routes): void
    {
        $routes->import($this->getProjectDir() . '/config/routes.yaml');
    }
}
