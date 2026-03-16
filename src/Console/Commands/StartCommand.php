<?php

namespace Xefi\LaravelOSDD\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(name: 'osdd:start')]
class StartCommand extends Command
{
    protected $name = 'osdd:start';

    protected $description = 'Prepare a fresh Laravel project for OSDD';

    private const LAYER_NAME = 'functional/users';
    private const LAYER_NAMESPACE = 'Functional\\Users';

    /**
     * The filesystem instance.
     */
    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    public function handle(): int
    {
        $layerPath = $this->resolveLayerBasePath() . '/users';

        $this->createUsersLayer($layerPath);
        $this->moveUserModel($layerPath);
        $this->moveUserFactory($layerPath);
        $this->moveUserMigrations($layerPath);
        $this->deleteAppDirectory();

        $this->components->info('Congratulations! Your project is ready for OSDD. Create your first layer with <options=bold>php artisan osdd:layer</>.');

        return self::SUCCESS;
    }

    private function resolveLayerBasePath(): string
    {
        $paths = config('osdd.layers.paths', []);

        return $paths['functional'] ?? $this->laravel->basePath('functional');
    }

    private function createUsersLayer(string $layerPath): void
    {
        $components = [
            'database/migrations',
            'database/factories',
            'database/seeders',
            'src/Models',
            'src/Factories',
            'src/Policies',
            'src/Providers',
        ];

        $this->createFile(
            $layerPath . '/composer.json',
            $this->resolveStub('composer'),
            [
                '{{ name }}' => self::LAYER_NAME,
                '{{ namespace }}' => str_replace('\\', '\\\\', self::LAYER_NAMESPACE),
            ],
        );

        foreach ($components as $component) {
            if ($component === 'database/seeders') {
                continue;
            }

            $componentPath = $layerPath . '/' . $component;

            $this->files->makeDirectory($componentPath, 0755, true, true);

            if ($component === 'src/Providers') {
                $this->createFile(
                    $componentPath . '/UsersServiceProvider.php',
                    $this->resolveStub('service-provider'),
                    ['{{ namespace }}' => self::LAYER_NAMESPACE, '{{ class }}' => 'UsersServiceProvider', '{{ seederClass }}' => 'UsersSeeder'],
                );
            } else {
                $this->files->put($componentPath . '/.gitkeep', '');
            }
        }

        $this->call('osdd:seeder', [
            'name' => 'UsersSeeder',
            '--layer' => self::LAYER_NAME,
        ]);

        $this->components->info('Layer <options=bold>' . self::LAYER_NAME . '</> created at <options=bold>' . $layerPath . '</>.');
    }

    private function moveUserModel(string $layerPath): void
    {
        $source = $this->laravel->basePath('app/Models/User.php');

        if (!$this->files->isFile($source)) {
            $this->components->warn('No User model found at app/Models/User.php, skipping.');
            return;
        }

        $contents = str_replace(
            [
                'namespace App\\Models;',
                '@use HasFactory<\\Database\\Factories\\UserFactory>',
            ],
            [
                'namespace Functional\\Users\\Models;',
                '@use HasFactory<\\Functional\\Users\\Database\\Factories\\UserFactory>',
            ],
            $this->files->get($source),
        );

        $this->files->put($layerPath . '/src/Models/User.php', $contents);

        $this->components->info('Moved User model to layer.');
    }

    private function moveUserFactory(string $layerPath): void
    {
        $source = $this->laravel->basePath('database/factories/UserFactory.php');

        if (!$this->files->isFile($source)) {
            $this->components->warn('No UserFactory found at database/factories/UserFactory.php, skipping.');
            return;
        }

        $contents = str_replace(
            [
                'namespace Database\\Factories;',
                'use App\\Models\\User;',
            ],
            [
                'namespace Functional\\Users\\Database\\Factories;',
                'use Functional\\Users\\Models\\User;',
            ],
            $this->files->get($source),
        );

        $this->files->put($layerPath . '/database/factories/UserFactory.php', $contents);

        $this->components->info('Moved UserFactory to layer.');
    }

    private function moveUserMigrations(string $layerPath): void
    {
        $migrationsPath = $this->laravel->basePath('database/migrations');
        $migrations = $this->files->glob($migrationsPath . '/*_create_users_table.php') ?: [];

        if (empty($migrations)) {
            $this->components->warn('No user migrations found, skipping.');
            return;
        }

        foreach ($migrations as $migration) {
            $this->files->move($migration, $layerPath . '/database/migrations/' . basename($migration));
        }

        $this->components->info('Moved ' . count($migrations) . ' user migration(s) to layer.');
    }

    private function deleteAppDirectory(): void
    {
        $appPath = $this->laravel->basePath('app');

        if (!$this->files->isDirectory($appPath)) {
            $this->components->warn('No app/ directory found, skipping.');
            return;
        }

        $this->files->deleteDirectory($appPath);

        $this->components->info('Deleted app/ directory.');
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
}
