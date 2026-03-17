<?php

namespace Xefi\LaravelOSDD\Console\Concerns;

trait RegistersLayerInComposer
{
    protected function registerLayerInComposer(string $name, string $layerPath): void
    {
        $composerPath = $this->laravel->basePath('composer.json');

        if (!$this->files->exists($composerPath)) {
            $this->components->warn('No composer.json found at project root, skipping Composer registration.');
            return;
        }

        $composer = json_decode($this->files->get($composerPath), true, 512, JSON_THROW_ON_ERROR);

        $relativePath = $this->relativeLayerPath($layerPath);

        $this->addPathRepository($composer, $relativePath);
        $this->addRequireEntry($composer, $name);

        $this->files->put(
            $composerPath,
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL,
        );

        $this->components->info("Registered <options=bold>{$name}</> in composer.json. Run <options=bold>composer update {$name}</> to install it.");
    }

    private function relativeLayerPath(string $layerPath): string
    {
        $base = rtrim(str_replace('\\', '/', $this->laravel->basePath()), '/') . '/';
        $layer = str_replace('\\', '/', $layerPath);

        return './' . ltrim(str_replace($base, '', $layer), '/');
    }

    private function addPathRepository(array &$composer, string $relativePath): void
    {
        $repositories = $composer['repositories'] ?? [];

        foreach ($repositories as $repo) {
            if (($repo['url'] ?? '') === $relativePath) {
                return;
            }
        }

        $repositories[] = ['type' => 'path', 'url' => $relativePath];
        $composer['repositories'] = $repositories;
    }

    private function addRequireEntry(array &$composer, string $name): void
    {
        if (!isset($composer['require'][$name])) {
            $composer['require'][$name] = '*';
        }
    }
}
