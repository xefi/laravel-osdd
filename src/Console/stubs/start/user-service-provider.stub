<?php

namespace Functional\Users\Providers;

use Functional\Users\Database\Seeders\UsersSeeder;
use Functional\Users\Models\User;
use Xefi\LaravelOSDD\LayerServiceProvider;

class UsersServiceProvider extends LayerServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');
            $this->loadSeeders([UsersSeeder::class]);
        }
    }

    public function register(): void
    {
        config(['auth.providers.users.model' => User::class]);
    }
}
