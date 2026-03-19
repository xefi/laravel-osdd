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
}
