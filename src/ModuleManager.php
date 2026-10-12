<?php

declare(strict_types=1);

namespace EchoFusion\ModuleManager;

use EchoFusion\Contracts\ModuleManager\ModuleInterface;
use EchoFusion\Contracts\ModuleManager\ModuleManagerInterface;
use EchoFusion\ModuleManager\Exceptions\DuplicateModuleException;
use EchoFusion\ModuleManager\Exceptions\DuplicateRouteException;
use EchoFusion\ModuleManager\Exceptions\ModuleException;
use EchoFusion\ModuleManager\Exceptions\ModuleNotFoundException;
use function array_is_list;
use function array_key_exists;
use function array_values;
use function is_array;
use function sprintf;

final class ModuleManager implements ModuleManagerInterface
{
    /**
     * @var array<non-empty-string, ModuleInterface>
     */
    private array $modules = [];

    /**
     * @param iterable<ModuleInterface> $modules
     *
     * @throws ModuleException
     */
    public function __construct(iterable $modules = [])
    {
        foreach ($modules as $module) {
            $this->register($module);
        }
    }

    public function register(ModuleInterface $module): void
    {
        $name = $module->getName();

        if ($this->has($name)) {
            throw new DuplicateModuleException(sprintf('Module "%s" is already registered.', $name));
        }

        $this->modules[$name] = $module;
    }

    public function has(string $name): bool
    {
        return array_key_exists($name, $this->modules);
    }

    public function get(string $name): ModuleInterface
    {
        return $this->modules[$name] ?? throw new ModuleNotFoundException(sprintf('Module "%s" is not registered.', $name));
    }

    public function all(): array
    {
        return $this->modules;
    }

    public function getConfig(): array
    {
        $config = [];
        foreach ($this->modules as $module) {
            $config = self::mergeConfig($config, $module->getConfig());
        }

        return $config;
    }

    public function getProviders(): array
    {
        $providers = [];
        foreach ($this->modules as $module) {
            foreach ($module->getProviders() as $provider) {
                $providers[$provider] = $provider;
            }
        }

        return array_values($providers);
    }

    /**
     * @throws DuplicateRouteException if two modules define a route with the same name
     */
    public function getRoutes(): array
    {
        $routes = [];
        $owners = [];
        foreach ($this->modules as $name => $module) {
            foreach ($module->getRoutes() as $routeName => $route) {
                if (array_key_exists($routeName, $routes)) {
                    throw new DuplicateRouteException(sprintf(
                        'Route "%s" is defined by module "%s" and by module "%s".',
                        $routeName,
                        $owners[$routeName],
                        $name,
                    ));
                }

                $routes[$routeName] = $route;
                $owners[$routeName] = $name;
            }
        }

        return $routes;
    }

    /**
     * Merge `$override` into `$base`: associative arrays are merged recursively, lists and scalars are replaced.
     *
     * @param array<array-key, mixed> $base
     * @param array<array-key, mixed> $override
     *
     * @return array<array-key, mixed>
     */
    private static function mergeConfig(array $base, array $override): array
    {
        foreach ($override as $key => $value) {
            if (
                is_array($value)
                && isset($base[$key])
                && is_array($base[$key])
                && !array_is_list($value)
                && !array_is_list($base[$key])
            ) {
                $base[$key] = self::mergeConfig($base[$key], $value);

                continue;
            }

            $base[$key] = $value;
        }

        return $base;
    }

    public function getTemplatePaths(): array
    {
        $paths = [];
        foreach ($this->modules as $name => $module) {
            $path = $module->getTemplatePath();
            if ($path !== null && $path !== '') {
                $paths[$name] = $path;
            }
        }

        return $paths;
    }
}
