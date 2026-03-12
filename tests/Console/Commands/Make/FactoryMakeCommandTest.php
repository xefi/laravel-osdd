<?php

namespace Xefi\LaravelOSDD\Tests\Console\Commands\Make;

use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;
use Xefi\LaravelOSDD\Tests\TestCase;

class FactoryMakeCommandTest extends TestCase
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

    public function testItGeneratesFactoryFileInCorrectPath(): void
    {
        $this->artisan('osdd:factory', ['name' => 'UserFactory', '--layer' => 'functional/users'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/users/database/factories/UserFactory.php');
    }

    public function testItGeneratesFactoryWithCorrectNamespace(): void
    {
        $this->artisan('osdd:factory', ['name' => 'UserFactory', '--layer' => 'functional/users'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\Users\Database\Factories;',
            'class UserFactory extends Factory',
        ], 'functional/users/database/factories/UserFactory.php');
    }

    public function testItPromptsForLayerWhenNotProvided(): void
    {
        $this->artisan('osdd:factory', ['name' => 'UserFactory'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/users', '', ['functional/users' => 'functional/users'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/users/database/factories/UserFactory.php');
    }

    public function testItGeneratesNestedFactoryInCorrectPath(): void
    {
        $this->artisan('osdd:factory', ['name' => 'Admin/UserFactory', '--layer' => 'functional/users'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/users/database/factories/Admin/UserFactory.php');
    }

    public function testItGeneratesNestedFactoryWithCorrectNamespace(): void
    {
        $this->artisan('osdd:factory', ['name' => 'Admin/UserFactory', '--layer' => 'functional/users'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\Users\Database\Factories\Admin;',
            'class UserFactory extends Factory',
        ], 'functional/users/database/factories/Admin/UserFactory.php');
    }
}
