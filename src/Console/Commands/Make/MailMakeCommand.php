<?php

namespace Xefi\LaravelOSDD\Console\Commands\Make;

use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:mail')]
class MailMakeCommand extends \Illuminate\Foundation\Console\MailMakeCommand
{
    use ChoosesOsddLayer;

    protected $name = 'osdd:mail';

    protected function rootNamespace(): string
    {
        return $this->resolveLayer()->manifest->rootNamespace();
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return rtrim($rootNamespace, '\\') . '\\Mail';
    }

    protected function getPath($name): string
    {
        $layer = $this->resolveLayer();
        $layerNamespace = rtrim($layer->manifest->rootNamespace(), '\\');

        $relative = Str::replaceFirst($layerNamespace . '\\Mail\\', '', $name);

        return $layer->path . '/src/Mail/' . str_replace('\\', '/', $relative) . '.php';
    }
}
