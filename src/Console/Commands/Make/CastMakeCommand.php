<?php

namespace Xefi\LaravelOSDD\Console\Commands\Make;

use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:cast')]
class CastMakeCommand extends \Illuminate\Foundation\Console\CastMakeCommand
{
    use ChoosesOsddLayer;

    protected $name = 'osdd:cast';

    protected function rootNamespace(): string
    {
        return $this->resolveLayer()->manifest->rootNamespace();
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return rtrim($rootNamespace, '\\') . '\\Casts';
    }

    protected function getPath($name): string
    {
        $layer = $this->resolveLayer();
        $layerNamespace = rtrim($layer->manifest->rootNamespace(), '\\');

        $relative = Str::replaceFirst($layerNamespace . '\\Casts\\', '', $name);

        return $layer->path . '/src/Casts/' . str_replace('\\', '/', $relative) . '.php';
    }
}
