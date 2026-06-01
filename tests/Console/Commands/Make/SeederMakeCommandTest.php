<?php

namespace Xefi\LaravelOSDD\Tests\Console\Commands\Make;

use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;
use Xefi\LaravelOSDD\Tests\TestCase;

class SeederMakeCommandTest extends TestCase
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
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/database/seeders'));
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Providers'));

        parent::tearDown();
    }

    public function testItGeneratesSeederFileInCorrectPath(): void
    {
        $this->artisan('osdd:seeder', ['name' => 'UserSeeder', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/database/seeders/UserSeeder.php');
    }

    public function testItGeneratesSeederWithCorrectNamespace(): void
    {
        $this->artisan('osdd:seeder', ['name' => 'UserSeeder', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Database\Seeders;',
            'class UserSeeder extends Seeder',
        ], 'functional/test-layer/database/seeders/UserSeeder.php');
    }

    public function testItPromptsForLayerWhenNotProvided(): void
    {
        $this->artisan('osdd:seeder', ['name' => 'UserSeeder'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/database/seeders/UserSeeder.php');
    }

    public function testItGeneratesNestedSeederInCorrectPath(): void
    {
        $this->artisan('osdd:seeder', ['name' => 'Admin/AdminSeeder', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/database/seeders/Admin/AdminSeeder.php');
    }

    public function testItGeneratesNestedSeederWithCorrectNamespace(): void
    {
        $this->artisan('osdd:seeder', ['name' => 'Admin/AdminSeeder', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Database\Seeders\Admin;',
            'class AdminSeeder extends Seeder',
        ], 'functional/test-layer/database/seeders/Admin/AdminSeeder.php');
    }

    public function testItRegistersSeederInServiceProvider(): void
    {
        $this->artisan('osdd:seeder', ['name' => 'UserSeeder', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains(
            ['$this->loadSeeders([\Functional\TestLayer\Database\Seeders\UserSeeder::class]);'],
            'functional/test-layer/src/Providers/TestLayerServiceProvider.php',
        );
    }

    public function testItRegistersNestedSeederWithQualifiedClass(): void
    {
        $this->artisan('osdd:seeder', ['name' => 'Admin/AdminSeeder', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains(
            ['$this->loadSeeders([\Functional\TestLayer\Database\Seeders\Admin\AdminSeeder::class]);'],
            'functional/test-layer/src/Providers/TestLayerServiceProvider.php',
        );
    }

    public function testRegistrationIsIdempotent(): void
    {
        $this->artisan('osdd:seeder', ['name' => 'UserSeeder', '--layer' => 'functional/test-layer']);
        $this->artisan('osdd:seeder', ['name' => 'UserSeeder', '--layer' => 'functional/test-layer']);

        $content = file_get_contents($this->providerPath);
        $count   = substr_count($content, 'Database\Seeders\UserSeeder::class');

        $this->assertSame(1, $count);
    }

    public function testItSkipsRegistrationWhenServiceProviderDoesNotExist(): void
    {
        $this->app['files']->delete($this->providerPath);

        $this->artisan('osdd:seeder', ['name' => 'UserSeeder', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/database/seeders/UserSeeder.php');
    }
}
