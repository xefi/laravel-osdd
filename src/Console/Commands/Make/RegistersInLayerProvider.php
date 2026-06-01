<?php

namespace Xefi\LaravelOSDD\Console\Commands\Make;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

trait RegistersInLayerProvider
{
    /**
     * Inject a statement at the end of the resolved layer service provider's boot() method.
     *
     * No-op when the provider is missing or already contains the statement, so it is
     * safe to call on every generation.
     */
    protected function registerInLayerProvider(string $statement, string $label): void
    {
        $layer        = $this->resolveLayer();
        $providerName = Str::studly($layer->manifest->package()) . 'ServiceProvider';
        $providerPath = $layer->path . '/src/Providers/' . $providerName . '.php';

        $files = app(Filesystem::class);

        if (!$files->exists($providerPath)) {
            return;
        }

        $content = $files->get($providerPath);

        if (str_contains($content, $statement)) {
            return;
        }

        $pattern = '/(\n[ \t]*\})(\s+)([ \t]*public\s+function\s+register\b)/';
        $updated = preg_replace($pattern, "\n        {$statement}$1$2$3", $content, 1);

        if ($updated === $content) {
            $this->components->warn("Could not register [{$label}] in [{$providerPath}]: unexpected formatting.");
            return;
        }

        $files->put($providerPath, $updated);
        $this->components->info("Registered [{$label}] in [{$providerPath}].");
    }
}
