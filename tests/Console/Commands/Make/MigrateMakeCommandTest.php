<?php

namespace Xefi\LaravelOSDD\Tests\Console\Commands\Make;

use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;
use Xefi\LaravelOSDD\Tests\TestCase;

class MigrateMakeCommandTest extends TestCase
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

        mkdir($layerPath . '/database/migrations', 0755, true);

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

    public function testItGeneratesMigrationFileInCorrectPath(): void
    {
        $this->artisan('osdd:migration', ['name' => 'create_users_table', '--layer' => 'functional/users'])
            ->assertExitCode(0);

        $files = glob($this->app->basePath('functional/users/database/migrations/*_create_users_table.php'));
        $this->assertNotEmpty($files);
    }

    public function testItPromptsForLayerWhenNotProvided(): void
    {
        $this->artisan('osdd:migration', ['name' => 'create_users_table'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/users', '', ['functional/users' => 'functional/users'])
            ->assertExitCode(0);

        $files = glob($this->app->basePath('functional/users/database/migrations/*_create_users_table.php'));
        $this->assertNotEmpty($files);
    }

    public function testItGeneratesMigrationWithCreateOption(): void
    {
        $this->artisan('osdd:migration', ['name' => 'create_posts_table', '--create' => 'posts', '--layer' => 'functional/users'])
            ->assertExitCode(0);

        $files = glob($this->app->basePath('functional/users/database/migrations/*_create_posts_table.php'));
        $this->assertNotEmpty($files);
    }
}
