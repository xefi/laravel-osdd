<?php

namespace Xefi\LaravelOSDD\Console\Commands\Make;

use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:provider')]
class ServiceProviderMakeCommand extends \Illuminate\Foundation\Console\ProviderMakeCommand
{
    use ChoosesOsddLayer;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'osdd:provider';

    protected function rootNamespace(): string
    {
        return $this->resolveLayer()->manifest->rootNamespace();
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return rtrim($rootNamespace, '\\') . '\\Providers';
    }

    protected function getPath($name): string
    {
        $layer = $this->resolveLayer();
        $layerNamespace = rtrim($layer->manifest->rootNamespace(), '\\');

        $relative = Str::replaceFirst($layerNamespace . '\\Providers\\', '', $name);

        return $layer->path . '/src/Providers/' . str_replace('\\', '/', $relative) . '.php';
    }

    public function handle(): bool|null
    {
        $result = parent::handle();

        if ($result === false) {
            return false;
        }

        $this->registerProviderInComposer();

        return $result;
    }

    protected function registerProviderInComposer(): void
    {
        $layer = $this->resolveLayer();
        $composerPath = $layer->path . '/composer.json';

        $composer = json_decode($this->files->get($composerPath), true);

        $providerClass = $this->qualifyClass($this->getNameInput());

        $existing = $composer['extra']['laravel']['providers'] ?? [];
        $existing[] = $providerClass;

        $composer['extra']['laravel']['providers'] = array_values(array_unique($existing));

        $this->files->put(
            $composerPath,
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n"
        );

        $this->components->info("Provider [{$providerClass}] registered in [{$composerPath}].");
    }
}
