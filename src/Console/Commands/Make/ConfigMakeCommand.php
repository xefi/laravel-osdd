<?php

namespace Xefi\LaravelOSDD\Console\Commands\Make;

use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:config')]
class ConfigMakeCommand extends \Illuminate\Foundation\Console\ConfigMakeCommand
{
    use ChoosesOsddLayer;

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

        $this->injectOverrideIntoServiceProvider();

        return $result;
    }

    private function injectOverrideIntoServiceProvider(): void
    {
        $layer = $this->resolveLayer();

        $providerName = Str::studly($layer->manifest->package()) . 'ServiceProvider';
        $providerPath = $layer->path . '/src/Providers/' . $providerName . '.php';

        if (!$this->files->exists($providerPath)) {
            return;
        }

        $configFile = Str::finish($this->argument('name'), '.php');
        $configKey  = Str::before($configFile, '.php');
        $line       = "\$this->overrideConfigFrom(__DIR__ . '/../../config/{$configFile}', '{$configKey}');";

        $content = $this->files->get($providerPath);

        if (str_contains($content, $line)) {
            return;
        }

        $updated = str_replace(
            "    }\n\n    public function register()",
            "        {$line}\n    }\n\n    public function register()",
            $content,
        );

        if ($updated === $content) {
            return;
        }

        $this->files->put($providerPath, $updated);
        $this->components->info("Added [overrideConfigFrom] for [{$configFile}] to [{$providerPath}].");
    }
}
