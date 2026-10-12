<?php

declare(strict_types=1);

namespace EchoFusion\ModuleManager\Tests;

use EchoFusion\Contracts\ServiceManager\ServiceManagerInterface;
use EchoFusion\Contracts\ServiceProvider\ServiceProviderInterface;

final class ProviderOne implements ServiceProviderInterface
{
    public function register(ServiceManagerInterface $container): void
    {
    }

    public function boot(ServiceManagerInterface $container, array $config): void
    {
    }
}
