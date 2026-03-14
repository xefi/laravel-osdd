<?php

namespace Xefi\LaravelOSDD\Tests\Console\Commands\Make;

use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;
use Xefi\LaravelOSDD\Tests\TestCase;

class ModelMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('osdd.layers.paths', [
            'functional' => $app->basePath('functional'),
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $layerPath = $this->app->basePath('functional/users');

        mkdir($layerPath, 0755, true);

        file_put_contents($layerPath . '/composer.json', json_encode([
            'name' => 'functional/users',
            'type' => 'layer',
            'autoload' => [
                'psr-4' => [
                    'Functional\\Users\\' => 'src/',
                ],
            ],
        ]));
    }

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional'));

        parent::tearDown();
    }

    public function testItGeneratesModelFileInCorrectPath(): void
    {
        $this->artisan('osdd:model', ['name' => 'User', '--layer' => 'functional/users'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/users/src/Models/User.php');
    }

    public function testItGeneratesModelWithCorrectNamespace(): void
    {
        $this->artisan('osdd:model', ['name' => 'User', '--layer' => 'functional/users'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\Users\Models;',
            'class User extends Model',
        ], 'functional/users/src/Models/User.php');
    }

    public function testItPromptsForLayerWhenNotProvided(): void
    {
        $this->artisan('osdd:model', ['name' => 'User'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/users', '', ['functional/users' => 'functional/users'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/users/src/Models/User.php');
    }

    public function testItGeneratesNestedModelInCorrectPath(): void
    {
        $this->artisan('osdd:model', ['name' => 'Admin/User', '--layer' => 'functional/users'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/users/src/Models/Admin/User.php');
    }

    public function testItGeneratesNestedModelWithCorrectNamespace(): void
    {
        $this->artisan('osdd:model', ['name' => 'Admin/User', '--layer' => 'functional/users'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\Users\Models\Admin;',
            'class User extends Model',
        ], 'functional/users/src/Models/Admin/User.php');
    }

    public function testItGeneratesFactoryAlongsideModel(): void
    {
        $this->artisan('osdd:model', ['name' => 'User', '--layer' => 'functional/users', '--factory' => true])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/users/src/Models/User.php');
        $this->assertFilenameExists('functional/users/database/factories/UserFactory.php');
    }
}
