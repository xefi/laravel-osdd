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
use Xefi\LaravelOSDD\Console\Commands\LayerCommand;
use Xefi\LaravelOSDD\Console\Commands\PhpunitCommand;
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
                PhpunitCommand::class,
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

        $this->app->singleton(MigrateMakeCommand::class, function ($app) {
            return new MigrateMakeCommand($app['migration.creator'], $app['composer']);
        });
    }
}