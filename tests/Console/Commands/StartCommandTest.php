<?php

namespace Xefi\LaravelOSDD\Tests\Console\Commands;

use Illuminate\Filesystem\Filesystem;
use Xefi\LaravelOSDD\Tests\TestCase;

class StartCommandTest extends TestCase
{
    protected string $projectPath;

    protected function getEnvironmentSetUp($app): void
    {
        $this->projectPath = sys_get_temp_dir() . '/osdd-start-test-' . uniqid();
        mkdir($this->projectPath, 0755, true);

        $app->setBasePath($this->projectPath);

        $app['config']->set('osdd.layers.paths', [
            'functional' => $this->projectPath . '/functional',
            'technical'  => $this->projectPath . '/technical',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        mkdir($this->projectPath . '/app', 0755, true);
        mkdir($this->projectPath . '/database/migrations', 0755, true);
        mkdir($this->projectPath . '/database/factories', 0755, true);

        file_put_contents($this->projectPath . '/composer.json', json_encode($this->fakeComposer(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
        mkdir($this->projectPath . '/config', 0755, true);
        file_put_contents($this->projectPath . '/config/app.php', '<?php return [];');

        mkdir($this->projectPath . '/bootstrap', 0755, true);
        file_put_contents($this->projectPath . '/bootstrap/providers.php', "<?php\n\nreturn [\n    App\\Providers\\AppServiceProvider::class,\n];\n");
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        (new Filesystem)->deleteDirectory($this->projectPath);
    }

    public function testItCreatesTheUsersLayer(): void
    {
        $this->artisan('osdd:start')
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $this->assertFileExists($this->projectPath . '/functional/users/composer.json');
        $this->assertFileExists($this->projectPath . '/functional/users/src/Providers/UsersServiceProvider.php');
        $this->assertDirectoryExists($this->projectPath . '/functional/users/database/migrations');
        $this->assertDirectoryExists($this->projectPath . '/functional/users/database/factories');
        $this->assertFileExists($this->projectPath . '/functional/users/database/seeders/UsersSeeder.php');
        $this->assertDirectoryExists($this->projectPath . '/functional/users/src/Models');
    }

    public function testItCreatesTheUsersLayerComposerJson(): void
    {
        $this->artisan('osdd:start')
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $contents = file_get_contents($this->projectPath . '/functional/users/composer.json');

        $this->assertStringContainsString('"name": "functional/users"', $contents);
        $this->assertStringContainsString('"type": "layer"', $contents);
        $this->assertStringContainsString('"Functional\\\\Users\\\\": "src/"', $contents);
        $this->assertStringContainsString('"Functional\\\\Users\\\\Database\\\\Seeders\\\\": "database/seeders/"', $contents);
        $this->assertStringContainsString('"Functional\\\\Users\\\\Database\\\\Factories\\\\": "database/factories/"', $contents);
        $this->assertStringContainsString('Functional\\\\Users\\\\Providers\\\\UsersServiceProvider', $contents);
    }

    public function testItCreatesUserModelFromStub(): void
    {
        $this->artisan('osdd:start')
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $this->assertFileExists($this->projectPath . '/functional/users/src/Models/User.php');

        $contents = file_get_contents($this->projectPath . '/functional/users/src/Models/User.php');

        $this->assertStringContainsString('namespace Functional\Users\Models;', $contents);
        $this->assertStringContainsString('class User extends Authenticatable', $contents);
        $this->assertStringContainsString('use Functional\Users\Database\Factories\UserFactory;', $contents);
        $this->assertStringContainsString('#[UseFactory(UserFactory::class)]', $contents);
        $this->assertStringContainsString('#[Fillable([', $contents);
        $this->assertStringContainsString('#[Hidden([', $contents);
        $this->assertStringContainsString("'password' => 'hashed'", $contents);
    }

    public function testItCreatesUserFactoryFromStub(): void
    {
        $this->artisan('osdd:start')
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $this->assertFileExists($this->projectPath . '/functional/users/database/factories/UserFactory.php');

        $contents = file_get_contents($this->projectPath . '/functional/users/database/factories/UserFactory.php');

        $this->assertStringContainsString('namespace Functional\Users\Database\Factories;', $contents);
        $this->assertStringContainsString('use Functional\Users\Models\User;', $contents);
        $this->assertStringContainsString('class UserFactory extends Factory', $contents);
    }

    public function testItGeneratesUsersSeederForUsersLayer(): void
    {
        $this->artisan('osdd:start')
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $contents = file_get_contents($this->projectPath . '/functional/users/database/seeders/UsersSeeder.php');

        $this->assertStringContainsString('namespace Functional\Users\Database\Seeders;', $contents);
        $this->assertStringContainsString('class UsersSeeder extends Seeder', $contents);
        $this->assertStringContainsString('use Functional\Users\Models\User;', $contents);
        $this->assertStringContainsString('User::factory()->count(10)->create();', $contents);
    }

    public function testItCreatesUserMigrationFromStub(): void
    {
        $this->artisan('osdd:start')
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $migrations = glob($this->projectPath . '/functional/users/database/migrations/*_create_users_table.php');

        $this->assertNotEmpty($migrations);

        $contents = file_get_contents($migrations[0]);

        $this->assertStringContainsString("Schema::create('users'", $contents);
        $this->assertStringContainsString('$table->string(\'email\')', $contents);
    }

    public function testItCreatesTheUsersServiceProvider(): void
    {
        $this->artisan('osdd:start')
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $contents = file_get_contents($this->projectPath . '/functional/users/src/Providers/UsersServiceProvider.php');

        $this->assertStringContainsString('namespace Functional\Users\Providers;', $contents);
        $this->assertStringContainsString('class UsersServiceProvider extends LayerServiceProvider', $contents);
        $this->assertStringContainsString("loadMigrationsFrom(__DIR__ . '/../../database/migrations')", $contents);
        $this->assertStringContainsString('loadSeeders([UsersSeeder::class])', $contents);
    }

    public function testItDeletesTheAppDirectory(): void
    {
        $this->artisan('osdd:start')
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $this->assertDirectoryDoesNotExist($this->projectPath . '/app');
    }

    public function testItDeletesTheDatabaseDirectory(): void
    {
        $this->artisan('osdd:start')
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $this->assertDirectoryDoesNotExist($this->projectPath . '/database');
    }

    public function testItDeletesTheConfigDirectory(): void
    {
        $this->artisan('osdd:start')
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $this->assertDirectoryDoesNotExist($this->projectPath . '/config');
    }

    public function testItUsesTheConfiguredFunctionalPath(): void
    {
        $custom = $this->projectPath . '/layers/functional';
        $this->app['config']->set('osdd.layers.paths', [
            'functional' => $custom,
            'technical'  => $this->projectPath . '/technical',
        ]);

        $this->artisan('osdd:start')
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $this->assertFileExists($custom . '/users/composer.json');
    }

    public function testItResetsBootstrapProvidersToEmptyArray(): void
    {
        $this->artisan('osdd:start')
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $contents = file_get_contents($this->projectPath . '/bootstrap/providers.php');

        $this->assertStringNotContainsString('AppServiceProvider', $contents);
        $this->assertStringNotContainsString('OsddServiceProvider', $contents);
        $this->assertStringContainsString('return [', $contents);
    }

    public function testItSkipsBootstrapProvidersGracefullyWhenMissing(): void
    {
        unlink($this->projectPath . '/bootstrap/providers.php');
        rmdir($this->projectPath . '/bootstrap');

        $this->artisan('osdd:start')
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);
    }

    public function testItRegistersUsersLayerAsPathRepositoryInComposerJson(): void
    {
        $this->artisan('osdd:start')
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $composer = json_decode(file_get_contents($this->projectPath . '/composer.json'), true);

        $urls = array_column($composer['repositories'] ?? [], 'url');
        $this->assertContains('./functional/users', $urls);

        $types = array_column($composer['repositories'], 'type');
        $this->assertContains('path', $types);
    }

    public function testItAddsUsersLayerToRequireInComposerJson(): void
    {
        $this->artisan('osdd:start')
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $composer = json_decode(file_get_contents($this->projectPath . '/composer.json'), true);

        $this->assertArrayHasKey('functional/users', $composer['require']);
        $this->assertSame('*', $composer['require']['functional/users']);
    }


    public function testItCreatesTheOsddLayer(): void
    {
        $this->artisan('osdd:start')
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $this->assertFileExists($this->projectPath . '/technical/osdd/composer.json');
        $this->assertFileExists($this->projectPath . '/technical/osdd/config/osdd.php');
        $this->assertFileExists($this->projectPath . '/technical/osdd/src/Providers/OsddServiceProvider.php');
    }

    public function testItCreatesTheOsddLayerComposerJson(): void
    {
        $this->artisan('osdd:start')
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $contents = file_get_contents($this->projectPath . '/technical/osdd/composer.json');

        $this->assertStringContainsString('"name": "technical/osdd"', $contents);
        $this->assertStringContainsString('"type": "layer"', $contents);
        $this->assertStringContainsString('"version": "1.0.0"', $contents);
        $this->assertStringContainsString('"Technical\\\\Osdd\\\\": "src/"', $contents);
        $this->assertStringContainsString('OsddServiceProvider', $contents);
    }

    public function testItCreatesTheOsddServiceProvider(): void
    {
        $this->artisan('osdd:start')
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $contents = file_get_contents($this->projectPath . '/technical/osdd/src/Providers/OsddServiceProvider.php');

        $this->assertStringContainsString('namespace Technical\Osdd\Providers;', $contents);
        $this->assertStringContainsString('class OsddServiceProvider extends LayerServiceProvider', $contents);
        $this->assertStringContainsString('overrideConfigFrom', $contents);
    }

    public function testItRegistersOsddLayerAsPathRepositoryInComposerJson(): void
    {
        $this->artisan('osdd:start')
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $composer = json_decode(file_get_contents($this->projectPath . '/composer.json'), true);

        $urls = array_column($composer['repositories'] ?? [], 'url');
        $this->assertContains('./technical/osdd', $urls);
    }

    public function testItAddsOsddLayerToRequireInComposerJson(): void
    {
        $this->artisan('osdd:start')
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $composer = json_decode(file_get_contents($this->projectPath . '/composer.json'), true);

        $this->assertArrayHasKey('technical/osdd', $composer['require']);
        $this->assertSame('*', $composer['require']['technical/osdd']);
    }

    public function testItCleansLegacyAutoloadFromComposerJson(): void
    {
        $this->artisan('osdd:start')
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $composer = json_decode(file_get_contents($this->projectPath . '/composer.json'), true);

        $psr4 = $composer['autoload']['psr-4'] ?? [];
        $this->assertArrayNotHasKey('App\\', $psr4);
        $this->assertArrayNotHasKey('Database\\Factories\\', $psr4);
        $this->assertArrayNotHasKey('Database\\Seeders\\', $psr4);
    }

    public function testItAsksForComposerUpdateConfirmation(): void
    {
        $this->artisan('osdd:start')
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);
    }

    // -------------------------------------------------------------------------

    private function fakeComposer(): array
    {
        return [
            'require' => new \stdClass(),
            'autoload' => [
                'psr-4' => [
                    'App\\'               => 'app/',
                    'Database\\Factories\\' => 'database/factories/',
                    'Database\\Seeders\\'  => 'database/seeders/',
                ],
            ],
        ];
    }
}
