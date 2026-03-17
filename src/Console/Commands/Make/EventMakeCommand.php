<?php

namespace Xefi\LaravelOSDD\Console\Commands\Make;

use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:event')]
class EventMakeCommand extends \Illuminate\Foundation\Console\EventMakeCommand
{
    use ChoosesOsddLayer;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'osdd:event';

    protected function rootNamespace(): string
    {
        return $this->resolveLayer()->manifest->rootNamespace();
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return rtrim($rootNamespace, '\\') . '\\Events';
    }

    protected function getPath($name): string
    {
        $layer = $this->resolveLayer();
        $layerNamespace = rtrim($layer->manifest->rootNamespace(), '\\');

        $relative = Str::replaceFirst($layerNamespace . '\\Events\\', '', $name);

        return $layer->path . '/src/Events/' . str_replace('\\', '/', $relative) . '.php';
    }
}
