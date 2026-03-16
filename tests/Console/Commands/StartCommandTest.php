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
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        mkdir($this->projectPath . '/app/Models', 0755, true);
        file_put_contents($this->projectPath . '/app/Models/User.php', $this->fakeUserModel());

        mkdir($this->projectPath . '/database/factories', 0755, true);
        file_put_contents($this->projectPath . '/database/factories/UserFactory.php', $this->fakeUserFactory());

        mkdir($this->projectPath . '/database/migrations', 0755, true);
        file_put_contents($this->projectPath . '/database/migrations/0001_01_01_000000_create_users_table.php', $this->fakeUserMigration());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        (new Filesystem)->deleteDirectory($this->projectPath);
    }

    public function testItCreatesTheUsersLayer(): void
    {
        $this->artisan('osdd:start')->assertExitCode(0);

        $this->assertFileExists($this->projectPath . '/functional/users/composer.json');
        $this->assertFileExists($this->projectPath . '/functional/users/src/Providers/UsersServiceProvider.php');
        $this->assertFileExists($this->projectPath . '/functional/users/database/migrations/.gitkeep');
        $this->assertFileExists($this->projectPath . '/functional/users/database/factories/.gitkeep');
        $this->assertFileExists($this->projectPath . '/functional/users/database/seeders/UsersSeeder.php');
        $this->assertFileExists($this->projectPath . '/functional/users/src/Models/.gitkeep');
        $this->assertFileExists($this->projectPath . '/functional/users/src/Factories/.gitkeep');
        $this->assertFileExists($this->projectPath . '/functional/users/src/Policies/.gitkeep');
    }

    public function testItCreatesTheUsersLayerComposerJson(): void
    {
        $this->artisan('osdd:start')->assertExitCode(0);

        $contents = file_get_contents($this->projectPath . '/functional/users/composer.json');

        $this->assertStringContainsString('"name": "functional/users"', $contents);
        $this->assertStringContainsString('"type": "layer"', $contents);
        $this->assertStringContainsString('"Functional\\\\Users\\\\": "src/"', $contents);
    }

    public function testItGeneratesUsersSeederForUsersLayer(): void
    {
        $this->artisan('osdd:start')->assertExitCode(0);

        $contents = file_get_contents($this->projectPath . '/functional/users/database/seeders/UsersSeeder.php');

        $this->assertStringContainsString('namespace Functional\Users\Database\Seeders;', $contents);
        $this->assertStringContainsString('class UsersSeeder extends Seeder', $contents);
    }

    public function testItCreatesTheUsersServiceProvider(): void
    {
        $this->artisan('osdd:start')->assertExitCode(0);

        $contents = file_get_contents($this->projectPath . '/functional/users/src/Providers/UsersServiceProvider.php');

        $this->assertStringContainsString('namespace Functional\Users\Providers;', $contents);
        $this->assertStringContainsString('class UsersServiceProvider extends LayerServiceProvider', $contents);
        $this->assertStringContainsString("loadMigrationsFrom(__DIR__ . '/../../database/migrations')", $contents);
        $this->assertStringContainsString('loadSeeders([UsersSeeder::class])', $contents);
    }

    public function testItMovesTheUserModelWithCorrectNamespace(): void
    {
        $this->artisan('osdd:start')->assertExitCode(0);

        $this->assertFileExists($this->projectPath . '/functional/users/src/Models/User.php');

        $contents = file_get_contents($this->projectPath . '/functional/users/src/Models/User.php');

        $this->assertStringContainsString('namespace Functional\Users\Models;', $contents);
        $this->assertStringContainsString('class User extends Authenticatable', $contents);
    }

    public function testItUpdatesTheUserModelHasFactoryDocblock(): void
    {
        $this->artisan('osdd:start')->assertExitCode(0);

        $contents = file_get_contents($this->projectPath . '/functional/users/src/Models/User.php');

        $this->assertStringContainsString('@use HasFactory<\Functional\Users\Database\Factories\UserFactory>', $contents);
    }

    public function testItMovesTheUserFactoryWithCorrectNamespace(): void
    {
        $this->artisan('osdd:start')->assertExitCode(0);

        $this->assertFileExists($this->projectPath . '/functional/users/database/factories/UserFactory.php');

        $contents = file_get_contents($this->projectPath . '/functional/users/database/factories/UserFactory.php');

        $this->assertStringContainsString('namespace Functional\Users\Database\Factories;', $contents);
        $this->assertStringContainsString('use Functional\Users\Models\User;', $contents);
        $this->assertStringContainsString('class UserFactory extends Factory', $contents);
    }

    public function testItMovesUserMigrationsToLayer(): void
    {
        $this->artisan('osdd:start')->assertExitCode(0);

        $this->assertFileExists($this->projectPath . '/functional/users/database/migrations/0001_01_01_000000_create_users_table.php');
        $this->assertFileDoesNotExist($this->projectPath . '/database/migrations/0001_01_01_000000_create_users_table.php');
    }

    public function testItDeletesTheAppDirectory(): void
    {
        $this->artisan('osdd:start')->assertExitCode(0);

        $this->assertDirectoryDoesNotExist($this->projectPath . '/app');
    }

    public function testItSkipsUserModelGracefullyWhenMissing(): void
    {
        unlink($this->projectPath . '/app/Models/User.php');

        $this->artisan('osdd:start')->assertExitCode(0);

        $this->assertFileDoesNotExist($this->projectPath . '/functional/users/src/Models/User.php');
    }

    public function testItSkipsUserFactoryGracefullyWhenMissing(): void
    {
        unlink($this->projectPath . '/database/factories/UserFactory.php');

        $this->artisan('osdd:start')->assertExitCode(0);

        $this->assertFileDoesNotExist($this->projectPath . '/functional/users/database/factories/UserFactory.php');
    }

    public function testItSkipsUserMigrationsGracefullyWhenMissing(): void
    {
        unlink($this->projectPath . '/database/migrations/0001_01_01_000000_create_users_table.php');

        $this->artisan('osdd:start')->assertExitCode(0);

        $this->assertFileDoesNotExist($this->projectPath . '/functional/users/database/migrations/0001_01_01_000000_create_users_table.php');
    }

    public function testItUsesTheConfiguredFunctionalPath(): void
    {
        $custom = $this->projectPath . '/layers/functional';
        $this->app['config']->set('osdd.layers.paths', ['functional' => $custom]);

        $this->artisan('osdd:start')->assertExitCode(0);

        $this->assertFileExists($custom . '/users/composer.json');
    }

    // -------------------------------------------------------------------------

    private function fakeUserModel(): string
    {
        return <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;
}
PHP;
    }

    private function fakeUserFactory(): string
    {
        return <<<'PHP'
<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        return ['name' => fake()->name()];
    }
}
PHP;
    }

    private function fakeUserMigration(): string
    {
        return <<<'PHP'
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
PHP;
    }
}
