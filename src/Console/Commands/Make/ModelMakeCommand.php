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

    protected function getStub(): string
    {
        if ($this->option('factory')) {
            return __DIR__ . '/../../stubs/make/model.factory.stub';
        }

        return parent::getStub();
    }

    protected function buildClass($name): string
    {
        $stub = parent::buildClass($name);

        if ($this->option('factory')) {
            $rootNamespace = rtrim($this->resolveLayer()->manifest->rootNamespace(), '\\');
            $modelClass    = class_basename($name);

            $stub = str_replace(
                ['{{ factoryNamespace }}', '{{ factoryClass }}'],
                [$rootNamespace . '\\Database\\Factories', $modelClass . 'Factory'],
                $stub,
            );
        }

        return $stub;
    }

    public function handle(): bool|null
    {
        // Bypass ModelMakeCommand::handle()'s interactive "additional components" prompt
        // by calling GeneratorCommand::handle() directly via grandparent scope binding.
        $grandparentHandle = \Closure::bind(
            fn() => parent::handle(),
            $this,
            \Illuminate\Foundation\Console\ModelMakeCommand::class
        );

        $result = $grandparentHandle();

        if ($result === false && ! $this->option('force')) {
            return false;
        }

        if ($this->option('all')) {
            $this->input->setOption('factory', true);
            $this->input->setOption('seed', true);
            $this->input->setOption('migration', true);
            $this->input->setOption('controller', true);
            $this->input->setOption('policy', true);
            $this->input->setOption('resource', true);
        }

        if ($this->option('factory')) {
            $this->createFactory();
        }

        if ($this->option('migration')) {
            $this->createMigration();
        }

        if ($this->option('seed')) {
            $this->createSeeder();
        }

        if ($this->option('controller') || $this->option('resource') || $this->option('api')) {
            $this->createController();
        } elseif ($this->option('requests')) {
            $this->createFormRequests();
        }

        if ($this->option('policy')) {
            $this->createPolicy();
        }

        return null;
    }

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
