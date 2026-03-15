<?php

namespace Xefi\LaravelOSDD\Console\Commands\Make;

use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:model')]
class ModelMakeCommand extends \Illuminate\Foundation\Console\ModelMakeCommand
{
    use ChoosesOsddLayer;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'osdd:model';

    protected function rootNamespace(): string
    {
        return $this->resolveLayer()->manifest->rootNamespace();
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return rtrim($rootNamespace, '\\') . '\\Models';
    }

    protected function getPath($name): string
    {
        $layer = $this->resolveLayer();
        $layerNamespace = rtrim($layer->manifest->rootNamespace(), '\\');

        $relative = Str::replaceFirst($layerNamespace . '\\Models\\', '', $name);

        return $layer->path . '/src/Models/' . str_replace('\\', '/', $relative) . '.php';
    }

    protected function createFactory(): void
    {
        $factory = Str::studly($this->argument('name'));

        $this->call('osdd:factory', [
            'name' => "{$factory}Factory",
            '--model' => $this->qualifyClass($this->getNameInput()),
            '--layer' => $this->resolveLayer()->manifest->name(),
        ]);
    }

    protected function createController(): void
    {
        $controller = Str::studly($this->argument('name'));

        $this->call('osdd:controller', array_filter([
            'name' => "{$controller}Controller",
            '--model' => $this->qualifyClass($this->getNameInput()),
            '--resource' => true,
            '--requests' => $this->option('requests'),
            '--layer' => $this->resolveLayer()->manifest->name(),
        ]));
    }

    protected function createFormRequests(): void
    {
        $request = Str::studly($this->argument('name'));

        $this->call('osdd:request', [
            'name' => "Store{$request}Request",
            '--layer' => $this->resolveLayer()->manifest->name(),
        ]);

        $this->call('osdd:request', [
            'name' => "Update{$request}Request",
            '--layer' => $this->resolveLayer()->manifest->name(),
        ]);
    }

    protected function createPolicy(): void
    {
        $policy = Str::studly($this->argument('name'));

        $this->call('osdd:policy', [
            'name' => "{$policy}Policy",
            '--model' => $this->qualifyClass($this->getNameInput()),
            '--layer' => $this->resolveLayer()->manifest->name(),
        ]);
    }

    protected function createSeeder(): void
    {
        $seeder = Str::studly($this->argument('name'));

        $this->call('osdd:seeder', [
            'name' => "{$seeder}Seeder",
            '--layer' => $this->resolveLayer()->manifest->name(),
        ]);
    }

    protected function createMigration(): void
    {
        $table = Str::snake(Str::pluralStudly(class_basename($this->argument('name'))));

        if ($this->option('pivot')) {
            $table = Str::singular($table);
        }

        $this->call('osdd:migration', [
            'name' => "create_{$table}_table",
            '--create' => $table,
            '--layer' => $this->resolveLayer()->manifest->name(),
        ]);
    }
}
