<?php

namespace Xefi\LaravelOSDD\Console\Commands\Make;

use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:resource')]
class ResourceMakeCommand extends \Illuminate\Foundation\Console\ResourceMakeCommand
{
    use ChoosesOsddLayer;

    protected $name = 'osdd:resource';

    protected function rootNamespace(): string
    {
        return $this->resolveLayer()->manifest->rootNamespace();
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return rtrim($rootNamespace, '\\') . '\\Http\\Resources';
    }

    protected function getPath($name): string
    {
        $layer = $this->resolveLayer();
        $layerNamespace = rtrim($layer->manifest->rootNamespace(), '\\');

        $relative = Str::replaceFirst($layerNamespace . '\\Http\\Resources\\', '', $name);

        return $layer->path . '/src/Http/Resources/' . str_replace('\\', '/', $relative) . '.php';
    }
}
