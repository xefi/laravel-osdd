<?php

namespace Xefi\LaravelOSDD\Tests\Console\Commands\Make;

use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;
use Xefi\LaravelOSDD\Tests\TestCase;

class MigrateMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        foreach (glob($this->app->basePath('functional/test-layer/database/migrations/*.php')) as $file) {
            $this->app['files']->delete($file);
        }

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
}
