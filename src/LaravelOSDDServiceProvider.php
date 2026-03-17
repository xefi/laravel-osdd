<?php

namespace Xefi\LaravelOSDD;

use Illuminate\Support\ServiceProvider;
use Xefi\LaravelOSDD\Console\Commands\Make\ControllerMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\FactoryMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\MigrateMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\ModelMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\PolicyMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\RequestMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\SeederMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\ServiceProviderMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\TestMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\CastMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\ChannelMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\ClassMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\ConfigMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\ConsoleMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\EnumMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\EventMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\ExceptionMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\InterfaceMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\JobMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\ListenerMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\MailMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\MiddlewareMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\NotificationMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\ObserverMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\ResourceMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\RuleMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\ScopeMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\TraitMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\Make\ViewMakeCommand;
use Xefi\LaravelOSDD\Console\Commands\LayerCommand;
use Xefi\LaravelOSDD\Console\Commands\PhpunitCommand;
use Xefi\LaravelOSDD\SeederRegistry;
use Xefi\LaravelOSDD\Console\Commands\SeedCommand;
use Xefi\LaravelOSDD\Console\Commands\StartCommand;

class LaravelOSDDServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                LayerCommand::class,
                StartCommand::class,
                ControllerMakeCommand::class,
                FactoryMakeCommand::class,
                MigrateMakeCommand::class,
                ModelMakeCommand::class,
                PolicyMakeCommand::class,
                RequestMakeCommand::class,
                SeederMakeCommand::class,
                ServiceProviderMakeCommand::class,
                TestMakeCommand::class,
                CastMakeCommand::class,
                ChannelMakeCommand::class,
                ClassMakeCommand::class,
                ConfigMakeCommand::class,
                ConsoleMakeCommand::class,
                EnumMakeCommand::class,
                EventMakeCommand::class,
                ExceptionMakeCommand::class,
                InterfaceMakeCommand::class,
                JobMakeCommand::class,
                ListenerMakeCommand::class,
                MailMakeCommand::class,
                MiddlewareMakeCommand::class,
                NotificationMakeCommand::class,
                ObserverMakeCommand::class,
                ResourceMakeCommand::class,
                RuleMakeCommand::class,
                ScopeMakeCommand::class,
                TraitMakeCommand::class,
                ViewMakeCommand::class,
                PhpunitCommand::class,
                SeedCommand::class,
            ]);
        }

        $this->publishes([
            __DIR__.'/../config/osdd.php' => config_path('osdd.php'),
        ]);
    }

    /**
     * Register any package services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/osdd.php', 'osdd'
        );

        $this->app->singleton(SeederRegistry::class, fn() => new SeederRegistry());

        $this->app->singleton(MigrateMakeCommand::class, function ($app) {
            return new MigrateMakeCommand($app['migration.creator'], $app['composer']);
        });
    }
}