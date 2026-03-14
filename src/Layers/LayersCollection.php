<?php

namespace Xefi\LaravelOSDD\Layers;

use Illuminate\Support\Collection;
use Symfony\Component\Finder\Finder;

class LayersCollection extends Collection
{
    public static function fromConfig(): self
    {
        return static::discover(...array_values(config('osdd.layers.paths', [])));
    }

    public static function discover(string ...$paths): self
    {
        return new self(
            collect($paths)
                ->flatMap(fn(string $path) => static::scanPath($path))
                ->all()
        );
    }

    private static function scanPath(string $path): array
    {
        if (!is_dir($path)) {
            return [];
        }

        $layers = [];

        foreach ((new Finder)->depth(0)->directories()->in($path) as $directory) {
            if (Layer::isValidLayerDirectory($directory)) {
                $layers[] = Layer::fromDirectory($directory);
            } else {
                array_push($layers, ...static::scanPath($directory->getRealPath()));
            }
        }

        return $layers;
    }
}
