<?php

namespace Xefi\LaravelOSDD\Console\Commands\Make;

use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:trait')]
class TraitMakeCommand extends \Illuminate\Foundation\Console\TraitMakeCommand
{
    use ChoosesOsddLayer;

    protected $name = 'osdd:trait';

    protected function rootNamespace(): string
    {
        return $this->resolveLayer()->manifest->rootNamespace();
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return rtrim($rootNamespace, '\\') . '\\Concerns';
    }

    protected function getPath($name): string
    {
        $layer = $this->resolveLayer();
        $layerNamespace = rtrim($layer->manifest->rootNamespace(), '\\');

        $relative = Str::replaceFirst($layerNamespace . '\\Concerns\\', '', $name);

        return $layer->path . '/src/Concerns/' . str_replace('\\', '/', $relative) . '.php';
    }
}
