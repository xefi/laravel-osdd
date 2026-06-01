<?php

namespace Xefi\LaravelOSDD;

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
     *
     * Unlike mergeConfigFrom(), the layer values take priority over whatever
     * is already loaded — so a layer can override a third-party package's
     * defaults (Horizon, Telescope, …) exactly like the app's own config
     * folder does. Call this from register() so the override is in place
     * before other packages boot and read their config.
     */
    protected function overrideConfigFrom(string $path, string $key): void
    {
        $config = $this->app->make('config');

        $config->set($key, array_replace_recursive(
            $config->get($key, []),
            require $path,
        ));
    }
}
