<?php

declare(strict_types=1);

namespace EchoFusion\ModuleManager\Tests;

use EchoFusion\Contracts\ModuleManager\ModuleInterface;
use EchoFusion\Contracts\ModuleManager\ModuleManagerInterface;
use EchoFusion\Contracts\ServiceProvider\ServiceProviderInterface;
use EchoFusion\ModuleManager\AbstractModule;
use EchoFusion\ModuleManager\Exceptions\DuplicateModuleException;
use EchoFusion\ModuleManager\Exceptions\DuplicateRouteException;
use EchoFusion\ModuleManager\Exceptions\ModuleNotFoundException;
use EchoFusion\ModuleManager\ModuleManager;
use PHPUnit\Framework\TestCase;

final class ModuleManagerTest extends TestCase
{
    public function testImplementsContract(): void
    {
        $this->assertInstanceOf(ModuleManagerInterface::class, new ModuleManager());
    }

    public function testEmptyManager(): void
    {
        $manager = new ModuleManager();

        $this->assertSame([], $manager->all());
        $this->assertSame([], $manager->getConfig());
        $this->assertSame([], $manager->getProviders());
        $this->assertSame([], $manager->getRoutes());
        $this->assertSame([], $manager->getTemplatePaths());
    }

    public function testRegisterGetHasAndOrder(): void
    {
        $blog = self::module('blog');
        $shop = self::module('shop');
        $manager = new ModuleManager([$blog]);
        $manager->register($shop);

        $this->assertTrue($manager->has('blog'));
        $this->assertFalse($manager->has('missing'));
        $this->assertSame($blog, $manager->get('blog'));
        $this->assertSame(['blog', 'shop'], array_keys($manager->all()));
    }

    public function testGetThrowsForUnknownModule(): void
    {
        $this->expectException(ModuleNotFoundException::class);
        $this->expectExceptionMessage('Module "nope" is not registered.');

        (new ModuleManager())->get('nope');
    }

    public function testRegisteringSameNameTwiceThrows(): void
    {
        $manager = new ModuleManager([self::module('blog')]);

        $this->expectException(DuplicateModuleException::class);
        $this->expectExceptionMessage('Module "blog" is already registered.');
        $manager->register(self::module('blog'));
    }

    public function testConfigIsDeepMergedInRegistrationOrder(): void
    {
        $manager = new ModuleManager([
            self::module('a', config: ['db' => ['host' => 'a', 'port' => 1], 'plugins' => ['x']]),
            self::module('b', config: ['db' => ['host' => 'b'], 'plugins' => ['y']]),
        ]);

        $this->assertSame(['db' => ['host' => 'b', 'port' => 1], 'plugins' => ['y']], $manager->getConfig());
    }

    public function testProvidersAreCollectedWithoutDuplicates(): void
    {
        $manager = new ModuleManager([
            self::module('a', providers: [ProviderOne::class, ProviderTwo::class]),
            self::module('b', providers: [ProviderTwo::class]),
        ]);

        $this->assertSame([ProviderOne::class, ProviderTwo::class], $manager->getProviders());
    }

    public function testRoutesAreCombined(): void
    {
        $manager = new ModuleManager([
            self::module('a', routes: ['a.index' => self::route('/a')]),
            self::module('b', routes: ['b.index' => self::route('/b')]),
        ]);

        $this->assertSame(['a.index', 'b.index'], array_keys($manager->getRoutes()));
    }

    public function testDuplicateRouteNameAcrossModulesThrows(): void
    {
        $manager = new ModuleManager([
            self::module('a', routes: ['home' => self::route('/a')]),
            self::module('b', routes: ['home' => self::route('/b')]),
        ]);

        $this->expectException(DuplicateRouteException::class);
        $this->expectExceptionMessage('Route "home" is defined by module "a" and by module "b".');
        $manager->getRoutes();
    }

    public function testTemplatePathsSkipModulesWithoutTemplates(): void
    {
        $manager = new ModuleManager([
            self::module('a', templatePath: '/app/a/views'),
            self::module('b'),
            self::module('c', templatePath: ''),
        ]);

        $this->assertSame(['a' => '/app/a/views'], $manager->getTemplatePaths());
    }

    public function testAbstractModuleDefaults(): void
    {
        $module = new class() extends AbstractModule {
            public function getName(): string
            {
                return 'plain';
            }
        };

        $this->assertInstanceOf(ModuleInterface::class, $module);
        $this->assertSame([], $module->getConfig());
        $this->assertSame([], $module->getProviders());
        $this->assertSame([], $module->getRoutes());
        $this->assertNull($module->getTemplatePath());
    }

    /**
     * @param array<string, mixed> $config
     * @param list<class-string<ServiceProviderInterface>> $providers
     * @param array<non-empty-string, array<string, mixed>> $routes
     */
    private static function module(
        string $name,
        array $config = [],
        array $providers = [],
        array $routes = [],
        ?string $templatePath = null,
    ): ModuleInterface {
        return new class($name, $config, $providers, $routes, $templatePath) extends AbstractModule {
            public function __construct(
                private string $name,
                private array $config,
                private array $providers,
                private array $routes,
                private ?string $templatePath,
            ) {
            }

            public function getName(): string
            {
                return $this->name;
            }

            public function getConfig(): array
            {
                return $this->config;
            }

            public function getProviders(): array
            {
                return $this->providers;
            }

            public function getRoutes(): array
            {
                return $this->routes;
            }

            public function getTemplatePath(): ?string
            {
                return $this->templatePath;
            }
        };
    }

    /**
     * @return array<string, mixed>
     */
    private static function route(string $path): array
    {
        return ['method' => 'GET', 'path' => $path, 'action' => 'handler'];
    }
}
