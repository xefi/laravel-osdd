<?php

namespace Xefi\LaravelOSDD\Console\Commands\Make;

use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:config')]
class ConfigMakeCommand extends \Illuminate\Foundation\Console\ConfigMakeCommand
{
    use ChoosesOsddLayer;
    use RegistersInLayerProvider;

    protected $name = 'osdd:config';

    protected $aliases = [];

    protected function rootNamespace(): string
    {
        return $this->resolveLayer()->manifest->rootNamespace();
    }

    protected function getPath($name): string
    {
        $layer = $this->resolveLayer();

        return $layer->path . '/config/' . Str::finish($this->argument('name'), '.php');
    }

    public function handle(): ?bool
    {
        $result = parent::handle();

        $configFile = Str::finish($this->argument('name'), '.php');
        $configKey  = Str::before($configFile, '.php');

        $this->registerInLayerProvider(
            "\$this->overrideConfigFrom(__DIR__ . '/../../config/{$configFile}', '{$configKey}');",
            "config:{$configKey}",
        );

        return $result;
    }
}
