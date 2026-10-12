# ModuleManager

[![CI](https://github.com/echo-fusion/modulemanager/actions/workflows/ci.yml/badge.svg)](https://github.com/echo-fusion/modulemanager/actions/workflows/ci.yml) [![Packagist](https://img.shields.io/packagist/v/echo-fusion/modulemanager.svg)](https://packagist.org/packages/echo-fusion/modulemanager) [![PHP](https://img.shields.io/badge/php-8.1%20%7C%208.2%20%7C%208.3-777bb4.svg)](https://www.php.net) [![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

**ModuleManager** is a registry for application modules. A module is a self-contained feature bundle (for example "Blog" or "Shop") that declares what it adds to the application **as data**: its configuration, service providers, routes and template directory. The manager collects and merges them so the application can wire everything in one place.

It implements `EchoFusion\Contracts\ModuleManager\ModuleManagerInterface` and modules implement `EchoFusion\Contracts\ModuleManager\ModuleInterface`, both from [echo-fusion/contracts](https://github.com/echo-fusion/contracts).

> Part of [EchoFusion](https://github.com/echo-fusion), a modular PHP framework built from small packages.

## Installation

```bash
composer require echo-fusion/modulemanager
```

## Requirements

* PHP 8.1, 8.2 or 8.3

## Usage

### Define a module

Extend `AbstractModule` and override only what the module provides (everything defaults to empty):

```php
use EchoFusion\ModuleManager\AbstractModule;

final class BlogModule extends AbstractModule
{
    public function getName(): string
    {
        return 'blog';
    }

    public function getConfig(): array
    {
        return ['blog' => ['posts_per_page' => 10]];
    }

    public function getProviders(): array
    {
        return [BlogServiceProvider::class];
    }

    public function getRoutes(): array
    {
        return [
            'blog.index' => [
                'method' => 'GET',
                'path' => '/blog',
                'action' => [BlogController::class, 'index'],
            ],
        ];
    }

    public function getTemplatePath(): ?string
    {
        return __DIR__ . '/../templates';
    }
}
```

The route format is the one accepted by `RouteLoaderInterface::fromArray()` of [echo-fusion/routemanager](https://github.com/echo-fusion/routemanager).

### Register modules

```php
use EchoFusion\ModuleManager\ModuleManager;

$modules = new ModuleManager([new BlogModule(), new ShopModule()]);
$modules->register(new AdminModule());   // or one at a time

$modules->has('blog');            // true
$modules->get('blog');            // the BlogModule instance
$modules->all();                  // ['blog' => ..., 'shop' => ..., 'admin' => ...]
```

### What the application gets

| Method | Result |
| --- | --- |
| `getConfig()` | All module configs deeply merged in registration order (later modules win; associative arrays are merged recursively, lists are replaced). |
| `getProviders()` | All service provider class names, without duplicates. |
| `getRoutes()` | All routes keyed by name. |
| `getTemplatePaths()` | `['blog' => '/path/to/templates', ...]` for modules that have templates. |

### Errors

All exceptions extend `EchoFusion\ModuleManager\Exceptions\ModuleException`:

* `DuplicateModuleException`: a module name was registered twice.
* `ModuleNotFoundException`: `get()` was called with an unknown name.
* `DuplicateRouteException`: two modules define a route with the same name (thrown by `getRoutes()`, naming both modules).

## Testing

```bash
composer test
```

## Credits

- [Amir Shadanfar](https://github.com/amir-shadanfar)

## License

The MIT License (MIT). Please see [LICENSE](LICENSE) for more information.
