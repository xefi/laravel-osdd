<?php

namespace Xefi\LaravelOSDD\Console\Commands\Make;

use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:scope')]
class ScopeMakeCommand extends \Illuminate\Foundation\Console\ScopeMakeCommand
{
    use ChoosesOsddLayer;

    protected $name = 'osdd:scope';

    protected function rootNamespace(): string
    {
        return $this->resolveLayer()->manifest->rootNamespace();
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return rtrim($rootNamespace, '\\') . '\\Models\\Scopes';
    }

    protected function getPath($name): string
    {
        $layer = $this->resolveLayer();
        $layerNamespace = rtrim($layer->manifest->rootNamespace(), '\\');

        $relative = Str::replaceFirst($layerNamespace . '\\Models\\Scopes\\', '', $name);

        return $layer->path . '/src/Models/Scopes/' . str_replace('\\', '/', $relative) . '.php';
    }
}
