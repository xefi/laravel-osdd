<?php

namespace Xefi\LaravelOSDD\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Process\Process;
use Xefi\LaravelOSDD\Console\Concerns\RegistersLayerInComposer;

use function Laravel\Prompts\confirm;

#[AsCommand(name: 'osdd:start')]
class StartCommand extends Command
{
    use RegistersLayerInComposer;

    protected $name = 'osdd:start';

    protected $description = 'Prepare a fresh Laravel project for OSDD';

    private const USERS_LAYER_NAME = 'functional/users';
    private const USERS_LAYER_NAMESPACE = 'Functional\\Users';
    private const OSDD_LAYER_NAME = 'technical/osdd';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    public function handle(): int
    {
        $usersLayerPath = $this->resolveLayerBasePath() . '/users';
        $osddLayerPath  = $this->resolveTechnicalBasePath() . '/osdd';

        $this->createUsersLayer($usersLayerPath);
        $this->createOsddLayer($osddLayerPath);
        $this->deleteDirectory('app');
        $this->deleteDirectory('database');
        $this->deleteDirectory('config');
        $this->cleanComposerAutoload();

        $this->components->info('Congratulations! Your project is ready for OSDD. Create your first layer with <options=bold>php artisan osdd:layer</>.');

        if (confirm('Run composer update now?', default: true)) {
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
        // Create layer composer.json
        $this->createFile($layerPath . '/composer.json', $this->resolveStub('composer'), [
            '{{ name }}'      => self::USERS_LAYER_NAME,
            '{{ namespace }}' => str_replace('\\', '\\\\', self::USERS_LAYER_NAMESPACE),
        ]);

        // Create service provider from layer stub
        $this->createFile(
            $layerPath . '/src/Providers/UsersServiceProvider.php',
            $this->resolveStub('service-provider'),
            [
                '{{ namespace }}'   => self::USERS_LAYER_NAMESPACE,
                '{{ class }}'       => 'UsersServiceProvider',
                '{{ seederClass }}' => 'UsersSeeder',
            ],
        );

        $this->injectProviderInComposerJson(
            $layerPath . '/composer.json',
            self::USERS_LAYER_NAMESPACE . '\\Providers\\UsersServiceProvider',
        );

        $this->registerLayerInComposer(self::USERS_LAYER_NAME, $layerPath);

        // Create start-specific files from stubs
        $this->createFile($layerPath . '/src/Models/User.php', $this->resolveStartStub('user-model'));
        $this->createFile($layerPath . '/database/factories/UserFactory.php', $this->resolveStartStub('user-factory'));
        $this->createFile($layerPath . '/database/seeders/UsersSeeder.php', $this->resolveStartStub('users-seeder'));
        $this->createFile(
            $layerPath . '/database/migrations/' . date('Y_m_d_His') . '_create_users_table.php',
            $this->resolveStartStub('create-users-table'),
        );

        $this->components->info('Layer <options=bold>' . self::USERS_LAYER_NAME . '</> created at <options=bold>' . $layerPath . '</>.');
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

        $this->normalizeComposerPsr4($composer);

        $this->files->put(
            $composerPath,
            json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL,
        );

        $this->components->info('Cleaned up legacy autoload entries from composer.json.');
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

    private function resolveStartStub(string $stub): string
    {
        return $this->files->get(__DIR__ . '/../stubs/start/' . $stub . '.stub');
    }
}
