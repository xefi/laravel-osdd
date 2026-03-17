<?php

namespace Xefi\LaravelOSDD\Console\Commands\Make;

use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:observer')]
class ObserverMakeCommand extends \Illuminate\Foundation\Console\ObserverMakeCommand
{
    use ChoosesOsddLayer;

    protected $name = 'osdd:observer';

    protected function rootNamespace(): string
    {
        return $this->resolveLayer()->manifest->rootNamespace();
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return rtrim($rootNamespace, '\\') . '\\Observers';
    }

    protected function getPath($name): string
    {
        $layer = $this->resolveLayer();
        $layerNamespace = rtrim($layer->manifest->rootNamespace(), '\\');

        $relative = Str::replaceFirst($layerNamespace . '\\Observers\\', '', $name);

        return $layer->path . '/src/Observers/' . str_replace('\\', '/', $relative) . '.php';
    }
}
