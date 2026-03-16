<?php

namespace Xefi\LaravelOSDD\Console\Commands\Make;

use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:test')]
class TestMakeCommand extends \Illuminate\Foundation\Console\TestMakeCommand
{
    use ChoosesOsddLayer;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'osdd:test';

    protected function rootNamespace(): string
    {
        return $this->resolveLayer()->manifest->rootNamespace();
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        $suffix = $this->option('unit') ? 'Tests\\Unit' : 'Tests\\Feature';

        return rtrim($rootNamespace, '\\') . '\\' . $suffix;
    }

    protected function getPath($name): string
    {
        $layer = $this->resolveLayer();
        $layerNamespace = rtrim($layer->manifest->rootNamespace(), '\\');

        $suffix = $this->option('unit') ? 'Tests\\Unit\\' : 'Tests\\Feature\\';
        $relative = Str::replaceFirst($layerNamespace . '\\' . $suffix, '', $name);

        $dir = $this->option('unit') ? 'tests/Unit/' : 'tests/Feature/';

        return $layer->path . '/' . $dir . str_replace('\\', '/', $relative) . '.php';
    }
}
