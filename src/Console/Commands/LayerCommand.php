<?php

namespace Xefi\LaravelOSDD\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Process\Process;
use Xefi\LaravelOSDD\Console\Concerns\RegistersLayerInComposer;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

#[AsCommand(name: 'osdd:layer')]
class LayerCommand extends Command
{
    use RegistersLayerInComposer;

    protected $signature = 'osdd:layer
        {name? : Layer name (vendor/package)}
        {--target-path= : Full path to the target directory (skips selection prompt)}
        {--generators=* : Generators to run (skips selection prompt)}';

    protected $description = 'Create a new OSDD layer';

    private const GENERATORS = [
        'migration',
        'model',
        'factory',
        'seeder',
        'service-provider',
        'test',
        'controller',
        'policy',
    ];

    private const DEFAULT_GENERATORS = ['migration', 'model', 'factory', 'service-provider', 'test'];

    protected $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    public function handle(): int
    {
        if ($name = $this->argument('name')) {
            $targetPath = $this->option('target-path') ?? $this->askForTargetPath();
        } else {
            [$vendor, $targetPath] = $this->askForVendorAndPath();
            $package = $this->askForPackage();
            $name = $vendor . '/' . $package;
        }

        $generators = $this->option('generators') ?: $this->askForGenerators();

        $this->generate($name, $targetPath, $generators);

        $this->components->info("Layer <options=bold>{$name}</> created at <options=bold>{$targetPath}</>.");

        if (confirm('Run composer update now?', default: true)) {
            $this->runComposerUpdate($name);
        }

        return self::SUCCESS;
    }

    private function askForVendorAndPath(): array
    {
        $paths = config('osdd.layers.paths');

        if (count($paths) === 1) {
            return [array_key_first($paths), reset($paths)];
        }

        $key = select(
            label: 'Where should the layer be created?',
            options: array_keys($paths),
        );

        return [$key, $paths[$key]];
    }

    private function askForTargetPath(): string
    {
        return $this->askForVendorAndPath()[1];
    }

    private function askForPackage(): string
    {
        return text(
            label: 'Layer name',
            placeholder: 'my-layer',
            required: true,
            validate: fn(string $value) => preg_match('/^[a-z0-9-]+$/', $value)
                ? null
                : 'Name must use lowercase letters, numbers and hyphens.',
        );
    }

    private function askForGenerators(): array
    {
        return multiselect(
            label: 'Which generators should be run?',
            options: self::GENERATORS,
            default: self::DEFAULT_GENERATORS,
            required: true,
        );
    }

    private function generate(string $name, string $targetPath, array $generators): void
    {
        [$vendor, $package] = explode('/', $name);

        $layerPath   = $targetPath . '/' . $package;
        $namespace   = $this->toNamespace($vendor, $package);
        $singular    = Str::singular(Str::studly($package));
        $pascal      = Str::studly($package);
        $pluralSnake = Str::snake(Str::pluralStudly($package));

        // Create composer.json first — layer must be discoverable before make commands run
        $this->createFile(
            $layerPath . '/composer.json',
            $this->resolveStub('composer'),
            [
                '{{ name }}'      => $name,
                '{{ namespace }}' => str_replace('\\', '\\\\', $namespace),
            ],
        );

        $withFactory = in_array('factory', $generators);
        $withModel   = in_array('model', $generators);

        // When model is also generated, --factory on osdd:model handles factory creation.
        // Remove it from the loop so we don't duplicate it.
        $effectiveGenerators = ($withFactory && $withModel)
            ? array_diff($generators, ['factory'])
            : $generators;

        foreach ($effectiveGenerators as $generator) {
            match ($generator) {
                'migration'        => $this->call('osdd:migration', ['name' => "create_{$pluralSnake}_table", '--create' => $pluralSnake, '--layer' => $name]),
                'model'            => $this->call('osdd:model', array_filter(['name' => $singular, '--layer' => $name, '--factory' => $withFactory ?: null])),
                'factory'          => $this->call('osdd:factory', ['name' => "{$singular}Factory", '--layer' => $name]),
                'seeder'           => $this->call('osdd:seeder', ['name' => "{$pascal}Seeder", '--layer' => $name]),
                'service-provider' => $this->generateServiceProvider($layerPath . '/src/Providers', $namespace, $package, $layerPath),
                'test'             => $this->call('osdd:test', ['name' => "{$pascal}Test", '--layer' => $name]),
                'controller'       => $this->call('osdd:controller', ['name' => "{$pascal}Controller", '--layer' => $name]),
                'policy'           => $this->call('osdd:policy', ['name' => "{$pascal}Policy", '--layer' => $name]),
                default            => null,
            };
        }

        $this->registerLayerInComposer($name, $layerPath);
    }

    private function generateServiceProvider(string $path, string $namespace, string $package, string $layerPath): void
    {
        $serviceProviderClass = $this->toServiceProviderClass($package);

        $this->files->makeDirectory($path, 0755, true, true);
        $this->createFile(
            $path . '/' . $serviceProviderClass . '.php',
            $this->resolveStub('service-provider'),
            [
                '{{ namespace }}'   => $namespace,
                '{{ class }}'       => $serviceProviderClass,
                '{{ seederClass }}' => $this->toSeederClass($package),
            ],
        );

        $this->injectProviderInComposerJson(
            $layerPath . '/composer.json',
            $namespace . '\\Providers\\' . $serviceProviderClass,
        );
    }

    private function createFile(string $path, string $contents, array $replacements = []): void
    {
        $directory = dirname($path);

        if (!$this->files->isDirectory($directory)) {
            $this->files->makeDirectory($directory, 0755, true, true);
        }

        $this->files->put($path, str_replace(array_keys($replacements), array_values($replacements), $contents));
    }

    private function resolveStub(string $stub): string
    {
        return $this->files->get(__DIR__ . '/../stubs/layer/' . $stub . '.stub');
    }

    private function toNamespace(string $vendor, string $package): string
    {
        return Str::pascal($vendor) . '\\' . Str::pascal($package);
    }

    private function toServiceProviderClass(string $package): string
    {
        return Str::pascal($package) . 'ServiceProvider';
    }

    private function toSeederClass(string $package): string
    {
        return Str::pascal($package) . 'Seeder';
    }

    private function runComposerUpdate(string $name): void
    {
        $this->components->info('Running composer update...');

        $process = new Process(
            ['composer', 'update', $name],
            $this->laravel->basePath(),
        );
        $process->setTimeout(null);
        $process->run(fn ($type, $buffer) => $this->output->write($buffer));

        if (!$process->isSuccessful()) {
            $this->components->error('composer update failed. You may need to run it manually.');
        }
    }
}
