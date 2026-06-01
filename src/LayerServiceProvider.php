<?php

namespace Xefi\LaravelOSDD;

use Illuminate\Contracts\Foundation\CachesRoutes;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

abstract class LayerServiceProvider extends ServiceProvider
{
    /**
     * @param class-string<\Illuminate\Database\Seeder>[] $seeders
     * @param int $priority Lower values run first; ties preserve registration order
     */
    protected function loadSeeders(array $seeders, int $priority = 0): void
    {
        $this->app->make(SeederRegistry::class)->push($priority, ...$seeders);
    }

    /**
     * Override an already-loaded config key with values from a file.
     * Unlike mergeConfigFrom(), the layer values take priority over the
     * package's own defaults (and over anything else loaded earlier).
     */
    protected function overrideConfigFrom(string $path, string $key): void
    {
        $this->app->booted(function () use ($path, $key) {
            $config = $this->app->make('config');
            $config->set($key, array_replace_recursive(
                $config->get($key, []),
                require $path,
            ));
        });
    }

    /**
     * Register a layer's route files using Laravel's standard conventions —
     * mirrors `Application::configure()->withRouting(...)`:
     *
     *   - web      → wrapped in the `web` middleware group
     *   - api      → wrapped in `api` middleware + the `apiPrefix` prefix
     *   - commands → required (when running in console)
     *   - channels → required (Broadcast::channel(...) definitions)
     *   - health   → registers a GET endpoint returning {"status":"ok"}
     *
     * For anything beyond these conventions (custom prefixes, route groups,
     * domains, ...), use Laravel's Route facade directly in boot().
     */
    protected function withRouting(
        array|string|null $web = null,
        array|string|null $api = null,
        ?string $commands = null,
        ?string $channels = null,
        ?string $health = null,
        string $apiPrefix = 'api',
    ): void {
        if ($this->app instanceof CachesRoutes && $this->app->routesAreCached()) {
            return;
        }

        foreach (Arr::wrap($web) as $path) {
            if (is_file($path)) {
                Route::middleware('web')->group($path);
            }
        }

        foreach (Arr::wrap($api) as $path) {
            if (is_file($path)) {
                Route::middleware('api')->prefix($apiPrefix)->group($path);
            }
        }

        if (is_string($health)) {
            Route::get($health, fn() => response()->json(['status' => 'ok']));
        }

        if (is_string($channels) && is_file($channels)) {
            require $channels;
        }

        if (is_string($commands) && is_file($commands) && $this->app->runningInConsole()) {
            require $commands;
        }
    }
}
