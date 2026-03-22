<?php

namespace Xefi\LaravelOSDD\Tests\Console\Commands\Make;

use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;
use Xefi\LaravelOSDD\Tests\TestCase;

class ModelMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Models'));
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Policies'));
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Http'));
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/database/factories'));
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/database/seeders'));

        foreach ($this->app['files']->glob($this->app->basePath('functional/test-layer/database/migrations/*.php')) as $file) {
            $this->app['files']->delete($file);
        }

        parent::tearDown();
    }

    public function testItGeneratesModelFileInCorrectPath(): void
    {
        $this->artisan('osdd:model', ['name' => 'User', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Models/User.php');
    }

    public function testItGeneratesModelWithCorrectNamespace(): void
    {
        $this->artisan('osdd:model', ['name' => 'User', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Models;',
            'class User extends Model',
        ], 'functional/test-layer/src/Models/User.php');
    }

    public function testItPromptsForLayerWhenNotProvided(): void
    {
        $this->artisan('osdd:model', ['name' => 'User'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Models/User.php');
    }

    public function testItPromptsForNameWhenNotProvided(): void
    {
        $this->artisan('osdd:model')
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->expectsQuestion('What should the model be named?', 'User')
            ->expectsChoice('Would you like any of the following?', [], ['seed' => 'Database Seeder', 'factory' => 'Factory', 'requests' => 'Form Requests', 'migration' => 'Migration', 'policy' => 'Policy', 'resource' => 'Resource Controller'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Models/User.php');
    }

    public function testItGeneratesNestedModelInCorrectPath(): void
    {
        $this->artisan('osdd:model', ['name' => 'Admin/User', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Models/Admin/User.php');
    }

    public function testItGeneratesNestedModelWithCorrectNamespace(): void
    {
        $this->artisan('osdd:model', ['name' => 'Admin/User', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Models\Admin;',
            'class User extends Model',
        ], 'functional/test-layer/src/Models/Admin/User.php');
    }

    public function testItGeneratesFactoryAlongsideModel(): void
    {
        $this->artisan('osdd:model', ['name' => 'User', '--layer' => 'functional/test-layer', '--factory' => true])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Models/User.php');
        $this->assertFilenameExists('functional/test-layer/database/factories/UserFactory.php');
    }

    public function testItAddsHasFactoryAndUseFactoryAttributeWhenFactoryIsGenerated(): void
    {
        $this->artisan('osdd:model', ['name' => 'User', '--layer' => 'functional/test-layer', '--factory' => true])
            ->assertExitCode(0);

        $this->assertFileContains([
            'use Functional\TestLayer\Database\Factories\UserFactory;',
            'use Illuminate\Database\Eloquent\Attributes\UseFactory;',
            'use Illuminate\Database\Eloquent\Factories\HasFactory;',
            '#[UseFactory(UserFactory::class)]',
            'use HasFactory;',
        ], 'functional/test-layer/src/Models/User.php');
    }

    public function testItDoesNotAddHasFactoryWhenNoFactoryIsGenerated(): void
    {
        $this->artisan('osdd:model', ['name' => 'User', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $contents = $this->app['files']->get($this->app->basePath('functional/test-layer/src/Models/User.php'));

        $this->assertStringNotContainsString('HasFactory', $contents);
        $this->assertStringNotContainsString('UseFactory', $contents);
    }

    public function testItGeneratesControllerAlongsideModel(): void
    {
        $this->artisan('osdd:model', ['name' => 'User', '--layer' => 'functional/test-layer', '--controller' => true, '--resource' => true])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Models/User.php');
        $this->assertFilenameExists('functional/test-layer/src/Http/Controllers/UserController.php');
    }

    public function testItGeneratesFormRequestsAlongsideModel(): void
    {
        $this->artisan('osdd:model', ['name' => 'User', '--layer' => 'functional/test-layer', '--requests' => true])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Models/User.php');
        $this->assertFilenameExists('functional/test-layer/src/Http/Requests/StoreUserRequest.php');
        $this->assertFilenameExists('functional/test-layer/src/Http/Requests/UpdateUserRequest.php');
    }

    public function testItGeneratesPolicyAlongsideModel(): void
    {
        $this->artisan('osdd:model', ['name' => 'User', '--layer' => 'functional/test-layer', '--policy' => true])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Models/User.php');
        $this->assertFilenameExists('functional/test-layer/src/Policies/UserPolicy.php');
    }

    public function testItGeneratesSeederAlongsideModel(): void
    {
        $this->artisan('osdd:model', ['name' => 'User', '--layer' => 'functional/test-layer', '--seed' => true])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Models/User.php');
        $this->assertFilenameExists('functional/test-layer/database/seeders/UserSeeder.php');
    }

    public function testItGeneratesMigrationAlongsideModel(): void
    {
        $this->artisan('osdd:model', ['name' => 'User', '--layer' => 'functional/test-layer', '--migration' => true])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Models/User.php');

        $migrations = $this->app['files']->glob($this->app->basePath('functional/test-layer/database/migrations/*.php'));
        $this->assertNotEmpty($migrations);
    }
}
