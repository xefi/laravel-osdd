<?php

namespace Xefi\LaravelOSDD\Tests\Console\Commands\Make;

use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;
use Xefi\LaravelOSDD\Tests\TestCase;

class SeederMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/database/seeders'));

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
}
