<?php

namespace Xefi\LaravelOSDD\Console\Commands\Make;

use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:channel')]
class ChannelMakeCommand extends \Illuminate\Foundation\Console\ChannelMakeCommand
{
    use ChoosesOsddLayer;

    protected $name = 'osdd:channel';

    protected function rootNamespace(): string
    {
        return $this->resolveLayer()->manifest->rootNamespace();
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return rtrim($rootNamespace, '\\') . '\\Broadcasting';
    }

    protected function getPath($name): string
    {
        $layer = $this->resolveLayer();
        $layerNamespace = rtrim($layer->manifest->rootNamespace(), '\\');

        $relative = Str::replaceFirst($layerNamespace . '\\Broadcasting\\', '', $name);

        return $layer->path . '/src/Broadcasting/' . str_replace('\\', '/', $relative) . '.php';
    }
}
