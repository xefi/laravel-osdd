<?php

namespace Xefi\LaravelOSDD\Tests\Console\Commands\Make;

use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;
use Xefi\LaravelOSDD\Tests\TestCase;

class MigrateMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    private string $providerPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->providerPath = $this->app->basePath('functional/test-layer/src/Providers/TestLayerServiceProvider.php');

        $this->app['files']->ensureDirectoryExists(dirname($this->providerPath));

        file_put_contents($this->providerPath, <<<'PHP'
<?php

namespace Functional\TestLayer\Providers;

use Xefi\LaravelOSDD\LayerServiceProvider;

class TestLayerServiceProvider extends LayerServiceProvider
{
    public function boot(): void
    {
        //
    }

    public function register(): void
    {
        //
    }
}
PHP);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->app->basePath('functional/test-layer/database/migrations/*.php')) as $file) {
            $this->app['files']->delete($file);
        }

        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Providers'));

        parent::tearDown();
    }

    public function testItGeneratesMigrationFileInCorrectPath(): void
    {
        $this->artisan('osdd:migration', ['name' => 'create_users_table', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $files = glob($this->app->basePath('functional/test-layer/database/migrations/*_create_users_table.php'));
        $this->assertNotEmpty($files);
    }

    public function testItPromptsForLayerWhenNotProvided(): void
    {
        $this->artisan('osdd:migration', ['name' => 'create_users_table'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $files = glob($this->app->basePath('functional/test-layer/database/migrations/*_create_users_table.php'));
        $this->assertNotEmpty($files);
    }

    public function testItGeneratesMigrationWithCreateOption(): void
    {
        $this->artisan('osdd:migration', ['name' => 'create_posts_table', '--create' => 'posts', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $files = glob($this->app->basePath('functional/test-layer/database/migrations/*_create_posts_table.php'));
        $this->assertNotEmpty($files);
    }

    public function testItRegistersMigrationsPathInServiceProvider(): void
    {
        $this->artisan('osdd:migration', ['name' => 'create_users_table', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains(
            ["\$this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');"],
            'functional/test-layer/src/Providers/TestLayerServiceProvider.php',
        );
    }

    public function testRegistrationIsIdempotent(): void
    {
        $this->artisan('osdd:migration', ['name' => 'create_users_table', '--layer' => 'functional/test-layer'])->assertExitCode(0);
        $this->artisan('osdd:migration', ['name' => 'create_posts_table', '--layer' => 'functional/test-layer'])->assertExitCode(0);

        $content = file_get_contents($this->providerPath);
        $count   = substr_count($content, 'loadMigrationsFrom(__DIR__');

        $this->assertSame(1, $count);
    }

    public function testItSkipsRegistrationWhenServiceProviderDoesNotExist(): void
    {
        $this->app['files']->delete($this->providerPath);

        $this->artisan('osdd:migration', ['name' => 'create_users_table', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $files = glob($this->app->basePath('functional/test-layer/database/migrations/*_create_users_table.php'));
        $this->assertNotEmpty($files);
    }
}
