<?php

namespace Xefi\LaravelOSDD;

use Illuminate\Support\ServiceProvider;
use Symfony\Component\Finder\Finder;
use Xefi\LaravelOSDD\Console\Commands\LayerCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\CastMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\ChannelMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\ClassMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\ConfigMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\ConsoleMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\ControllerMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\EnumMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\EventMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\ExceptionMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\FactoryMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\InterfaceMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\JobMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\ListenerMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\MailMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\MiddlewareMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\MigrateMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\ModelMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\NotificationMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\ObserverMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\PolicyMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\RequestMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\ResourceMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\RuleMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\ScopeMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\SeederMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\ServiceProviderMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\TestMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\TraitMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\ViewMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\PhpunitCommand;
use Xefi\LaravelOSDD\Console\Commands\SeedCommand;
use Xefi\LaravelOSDD\Console\Commands\StartCommand;
use Xefi\LaravelOSDD\Layers\LayersCollection;

class LaravelOSDDServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->registerCommands();
        }

        $this->publishConfig();

        $this->app->booted(function () {
            if (in_array('tinker', $_SERVER['argv'] ?? [])) {
                $this->registerTinkerAliases();
            }
        });
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/osdd.php', 'osdd');

        $this->app->singleton(SeederRegistry::class, fn() => new SeederRegistry());

        $this->app->singleton(MigrateMakeCommand::class, function ($app) {
            return new MigrateMakeCommand($app['migration.creator'], $app['composer']);
        });
    }

    private function registerCommands(): void
    {
        $this->commands([
            LayerCommand::class,
            StartCommand::class,
            PhpunitCommand::class,
            SeedCommand::class,
            CastMakeCommand::class,
            ChannelMakeCommand::class,
            ClassMakeCommand::class,
            ConfigMakeCommand::class,
            ConsoleMakeCommand::class,
            ControllerMakeCommand::class,
            EnumMakeCommand::class,
            EventMakeCommand::class,
            ExceptionMakeCommand::class,
            FactoryMakeCommand::class,
            InterfaceMakeCommand::class,
            JobMakeCommand::class,
            ListenerMakeCommand::class,
            MailMakeCommand::class,
            MiddlewareMakeCommand::class,
            MigrateMakeCommand::class,
            ModelMakeCommand::class,
            NotificationMakeCommand::class,
            ObserverMakeCommand::class,
            PolicyMakeCommand::class,
            RequestMakeCommand::class,
            ResourceMakeCommand::class,
            RuleMakeCommand::class,
            ScopeMakeCommand::class,
            SeederMakeCommand::class,
            ServiceProviderMakeCommand::class,
            TestMakeCommand::class,
            TraitMakeCommand::class,
            ViewMakeCommand::class,
        ]);
    }

    private function publishConfig(): void
    {
        $this->publishes([
            __DIR__.'/../config/osdd.php' => config_path('osdd.php'),
        ]);
    }

    private function registerTinkerAliases(): void
    {
        [$namespaces, $map] = $this->buildLayerClassMap();

        $this->app['config']->set('tinker.alias', array_merge(
            $this->app['config']->get('tinker.alias', []),
            $namespaces,
        ));

        spl_autoload_register(function (string $class) use ($map) {
            if (str_contains($class, '\\') || ! isset($map[$class])) {
                return;
            }

            require_once $map[$class]['path'];
            class_alias($map[$class]['fqcn'], $class);
        });
    }

    private function buildLayerClassMap(): array
    {
        $namespaces = [];
        $map        = [];

        foreach (LayersCollection::fromConfig() as $layer) {
            $namespace = $layer->manifest->rootNamespace();
            $srcPath   = rtrim($layer->manifest->srcPath($layer->path), '/\\');

            $namespaces[] = $namespace;

            if (! is_dir($srcPath)) {
                continue;
            }

            foreach ((new Finder())->files()->name('*.php')->in($srcPath) as $file) {
                $relative  = substr($file->getRealPath(), strlen($srcPath) + 1);
                $fqcn      = $namespace . str_replace([DIRECTORY_SEPARATOR, '.php'], ['\\', ''], $relative);
                $shortName = basename($relative, '.php');

                $map[$shortName] ??= ['fqcn' => $fqcn, 'path' => $file->getRealPath()];
            }
        }

        return [$namespaces, $map];
    }
}
