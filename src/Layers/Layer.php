<?php

namespace Xefi\LaravelOSDD\Layers;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\SplFileInfo;

class Layer
{
    private function __construct(
        public readonly string $path,
        public readonly LayerManifest $manifest,
    ) {}

    public static function fromDirectory(SplFileInfo $directory): self
    {
        return new self(
            path: $directory->getRealPath(),
            manifest: LayerManifest::fromPath($directory->getRealPath() . '/composer.json'),
        );
    }

    public static function isValidLayerDirectory(SplFileInfo $directory): bool
    {
        $composerPath = $directory->getRealPath() . '/composer.json';

        if (!app(Filesystem::class)->isFile($composerPath)) {
            return false;
        }

        return LayerManifest::fromPath($composerPath)->isLayer();
    }
}
