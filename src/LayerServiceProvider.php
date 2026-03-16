<?php

namespace Xefi\LaravelOSDD;

use Illuminate\Support\ServiceProvider;

abstract class LayerServiceProvider extends ServiceProvider
{
    /**
     * @param class-string<\Illuminate\Database\Seeder>[] $seeders
     */
    protected function loadSeeders(array $seeders): void
    {
        $this->app->make(SeederRegistry::class)->push(...$seeders);
    }
}
