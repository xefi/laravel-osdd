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
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/database/factories'));

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
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer', 'functional/users' => 'functional/users'])
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
}
