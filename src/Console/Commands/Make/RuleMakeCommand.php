<?php

namespace Xefi\LaravelOSDD\Console\Commands\Make;

use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:rule')]
class RuleMakeCommand extends \Illuminate\Foundation\Console\RuleMakeCommand
{
    use ChoosesOsddLayer;

    protected $name = 'osdd:rule';

    protected function rootNamespace(): string
    {
        return $this->resolveLayer()->manifest->rootNamespace();
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return rtrim($rootNamespace, '\\') . '\\Rules';
    }

    protected function getPath($name): string
    {
        $layer = $this->resolveLayer();
        $layerNamespace = rtrim($layer->manifest->rootNamespace(), '\\');

        $relative = Str::replaceFirst($layerNamespace . '\\Rules\\', '', $name);

        return $layer->path . '/src/Rules/' . str_replace('\\', '/', $relative) . '.php';
    }
}
