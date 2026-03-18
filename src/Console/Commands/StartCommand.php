<?php

namespace Xefi\LaravelOSDD\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Process\Process;
use Xefi\LaravelOSDD\Console\Concerns\RegistersLayerInComposer;

#[AsCommand(name: 'osdd:start')]
class StartCommand extends Command
{
    use RegistersLayerInComposer;
    protected $name = 'osdd:start';

    protected $description = 'Prepare a fresh Laravel project for OSDD';

    private const USERS_LAYER_NAME = 'functional/users';
    private const USERS_LAYER_NAMESPACE = 'Functional\\Users';
    private const OSDD_LAYER_NAME = 'technical/osdd';

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
        $usersLayerPath = $this->resolveLayerBasePath() . '/users';
        $osddLayerPath = $this->resolveTechnicalBasePath() . '/osdd';

        $this->createUsersLayer($usersLayerPath);
        $this->moveUserModel($usersLayerPath);
        $this->moveUserFactory($usersLayerPath);
        $this->moveUserMigrations($usersLayerPath);
        $this->deleteDirectory('app');
        $this->deleteDirectory('database');
        $this->deleteDirectory('config');
        $this->cleanComposerAutoload();
        $this->createOsddLayer($osddLayerPath);

        $this->components->info('Congratulations! Your project is ready for OSDD. Create your first layer with <options=bold>php artisan osdd:layer</>.');

        if ($this->confirm('Run <options=bold>composer update</> now?')) {
            $this->runComposerUpdate();
        }

        return self::SUCCESS;
    }

    private function resolveLayerBasePath(): string
    {
        $paths = config('osdd.layers.paths', []);

        return $paths['functional'] ?? $this->laravel->basePath('functional');
    }

    private function resolveTechnicalBasePath(): string
    {
        $paths = config('osdd.layers.paths', []);

        return $paths['technical'] ?? $this->laravel->basePath('technical');
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
                '{{ name }}' => self::USERS_LAYER_NAME,
                '{{ namespace }}' => str_replace('\\', '\\\\', self::USERS_LAYER_NAMESPACE),
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
                    ['{{ namespace }}' => self::USERS_LAYER_NAMESPACE, '{{ class }}' => 'UsersServiceProvider', '{{ seederClass }}' => 'UsersSeeder'],
                );
            }
        }

        $this->createFile(
            $layerPath . '/database/seeders/UsersSeeder.php',
            $this->resolveStub('users-seeder'),
        );

        $this->registerLayerInComposer(self::USERS_LAYER_NAME, $layerPath);

        $this->components->info('Layer <options=bold>' . self::USERS_LAYER_NAME . '</> created at <options=bold>' . $layerPath . '</>.');
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

    private function cleanComposerAutoload(): void
    {
        $composerPath = $this->laravel->basePath('composer.json');

        if (!$this->files->exists($composerPath)) {
            $this->components->warn('No composer.json found, skipping autoload cleanup.');
            return;
        }

        $composer = json_decode($this->files->get($composerPath), true, 512, JSON_THROW_ON_ERROR);

        foreach (['App\\', 'Database\\Factories\\', 'Database\\Seeders\\'] as $key) {
            unset($composer['autoload']['psr-4'][$key]);
            unset($composer['autoload-dev']['psr-4'][$key]);
        }

        $this->files->put(
            $composerPath,
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL,
        );

        $this->components->info('Cleaned up legacy autoload entries from composer.json.');
    }

    private function createOsddLayer(string $layerPath): void
    {
        $this->createFile($layerPath . '/composer.json', $this->resolveStub('osdd-composer'));
        $this->createFile($layerPath . '/config/osdd.php', $this->resolveStub('osdd-config'));
        $this->createFile($layerPath . '/src/Providers/OsddServiceProvider.php', $this->resolveStub('osdd-service-provider'));

        $this->registerLayerInComposer(self::OSDD_LAYER_NAME, $layerPath);
        $this->injectOsddProvider();

        $this->components->info('Layer <options=bold>' . self::OSDD_LAYER_NAME . '</> created at <options=bold>' . $layerPath . '</>.');
    }

    private function injectOsddProvider(): void
    {
        $path = $this->laravel->basePath('bootstrap/providers.php');

        if (!$this->files->exists($path)) {
            $this->components->warn('No bootstrap/providers.php found, skipping provider injection.');
            return;
        }

        $this->files->put($path, "<?php\n\nreturn [\n    Technical\\Osdd\\Providers\\OsddServiceProvider::class,\n];\n");

        $this->components->info('Registered OsddServiceProvider in bootstrap/providers.php.');
    }

    private function runComposerUpdate(): void
    {
        $this->components->info('Running composer update...');

        $process = new Process(
            ['composer', 'update', self::USERS_LAYER_NAME, self::OSDD_LAYER_NAME],
            $this->laravel->basePath(),
        );
        $process->setTimeout(null);
        $process->run(fn ($type, $buffer) => $this->output->write($buffer));

        if (!$process->isSuccessful()) {
            $this->components->error('composer update failed. You may need to run it manually.');
        }
    }

    private function deleteDirectory(string $dir): void
    {
        $path = $this->laravel->basePath($dir);

        if (!$this->files->isDirectory($path)) {
            $this->components->warn("No {$dir}/ directory found, skipping.");
            return;
        }

        if (!$this->files->deleteDirectory($path)) {
            $this->components->error("Failed to delete {$dir}/ directory.");
            return;
        }

        $this->components->info("Deleted {$dir}/ directory.");
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
