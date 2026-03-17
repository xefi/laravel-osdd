<?php

namespace Xefi\LaravelOSDD\Console\Commands\Make;

use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:enum')]
class EnumMakeCommand extends \Illuminate\Foundation\Console\EnumMakeCommand
{
    use ChoosesOsddLayer;

    protected $name = 'osdd:enum';

    protected function rootNamespace(): string
    {
        return $this->resolveLayer()->manifest->rootNamespace();
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return rtrim($rootNamespace, '\\') . '\\Enums';
    }

    protected function getPath($name): string
    {
        $layer = $this->resolveLayer();
        $layerNamespace = rtrim($layer->manifest->rootNamespace(), '\\');

        $relative = Str::replaceFirst($layerNamespace . '\\Enums\\', '', $name);

        return $layer->path . '/src/Enums/' . str_replace('\\', '/', $relative) . '.php';
    }
}
