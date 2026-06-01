<?php

namespace Technical\Osdd\Providers;

use Xefi\LaravelOSDD\LayerServiceProvider;

class OsddServiceProvider extends LayerServiceProvider
{
    public function boot(): void
    {
        //
    }

    public function register(): void
    {
        $this->overrideConfigFrom(__DIR__ . '/../../config/osdd.php', 'osdd');
    }
}
