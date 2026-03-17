<?php

namespace Xefi\LaravelOSDD\Console\Commands\Make;

use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:job')]
class JobMakeCommand extends \Illuminate\Foundation\Console\JobMakeCommand
{
    use ChoosesOsddLayer;

    protected $name = 'osdd:job';

    protected function rootNamespace(): string
    {
        return $this->resolveLayer()->manifest->rootNamespace();
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return rtrim($rootNamespace, '\\') . '\\Jobs';
    }

    protected function getPath($name): string
    {
        $layer = $this->resolveLayer();
        $layerNamespace = rtrim($layer->manifest->rootNamespace(), '\\');

        $relative = Str::replaceFirst($layerNamespace . '\\Jobs\\', '', $name);

        return $layer->path . '/src/Jobs/' . str_replace('\\', '/', $relative) . '.php';
    }
}
