<?php

namespace Xefi\LaravelOSDD\Console\Commands\Make;

use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:view')]
class ViewMakeCommand extends \Illuminate\Foundation\Console\ViewMakeCommand
{
    use ChoosesOsddLayer;

    protected $name = 'osdd:view';

    protected function rootNamespace(): string
    {
        return $this->resolveLayer()->manifest->rootNamespace();
    }

    protected function getPath($name): string
    {
        $layer = $this->resolveLayer();

        $nameInput = str_replace(['\\', '.'], '/', trim($this->argument('name')));

        return $layer->path . '/resources/views/' . $nameInput . '.' . $this->option('extension');
    }
}
