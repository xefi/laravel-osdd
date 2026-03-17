<?php

namespace Xefi\LaravelOSDD\Console\Commands\Make;

use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:listener')]
class ListenerMakeCommand extends \Illuminate\Foundation\Console\ListenerMakeCommand
{
    use ChoosesOsddLayer;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'osdd:listener';

    protected function rootNamespace(): string
    {
        return $this->resolveLayer()->manifest->rootNamespace();
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return rtrim($rootNamespace, '\\') . '\\Listeners';
    }

    protected function getPath($name): string
    {
        $layer = $this->resolveLayer();
        $layerNamespace = rtrim($layer->manifest->rootNamespace(), '\\');

        $relative = Str::replaceFirst($layerNamespace . '\\Listeners\\', '', $name);

        return $layer->path . '/src/Listeners/' . str_replace('\\', '/', $relative) . '.php';
    }

    protected function buildClass($name): string
    {
        $event = $this->option('event') ?? '';

        if ($event !== '' && !str_starts_with($event, 'Illuminate') && !str_starts_with($event, '\\')) {
            $layerNamespace = rtrim($this->resolveLayer()->manifest->rootNamespace(), '\\');
            $event = $layerNamespace . '\\Events\\' . str_replace('/', '\\', $event);
        }

        // Reproduce GeneratorCommand::buildClass to avoid the laravel->getNamespace() call in the parent
        $stub = $this->files->get($this->getStub());
        $base = $this->replaceNamespace($stub, $name)->replaceClass($stub, $name);

        if ($event === '') {
            return $base;
        }

        $base = str_replace(['DummyEvent', '{{ event }}'], class_basename($event), $base);

        return str_replace(['DummyFullEvent', '{{ eventNamespace }}'], trim($event, '\\'), $base);
    }
}
