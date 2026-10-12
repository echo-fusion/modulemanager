<?php

declare(strict_types=1);

namespace EchoFusion\ModuleManager;

use EchoFusion\Contracts\ModuleManager\ModuleInterface;

/**
 * Convenience base class: a module only overrides what it provides.
 */
abstract class AbstractModule implements ModuleInterface
{
    public function getConfig(): array
    {
        return [];
    }

    public function getProviders(): array
    {
        return [];
    }

    public function getRoutes(): array
    {
        return [];
    }

    public function getTemplatePath(): ?string
    {
        return null;
    }
}
