<?php

namespace Xefi\LaravelOSDD\Tests\Console\Commands\Make;

use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;
use Xefi\LaravelOSDD\Tests\TestCase;

class FactoryMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/database/factories'));

        parent::tearDown();
    }

    public function testItGeneratesFactoryFileInCorrectPath(): void
    {
        $this->artisan('osdd:factory', ['name' => 'UserFactory', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/database/factories/UserFactory.php');
    }

    public function testItGeneratesFactoryWithCorrectNamespace(): void
    {
        $this->artisan('osdd:factory', ['name' => 'UserFactory', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Database\Factories;',
            'class UserFactory extends Factory',
        ], 'functional/test-layer/database/factories/UserFactory.php');
    }

    public function testItPromptsForLayerWhenNotProvided(): void
    {
        $this->artisan('osdd:factory', ['name' => 'UserFactory'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/database/factories/UserFactory.php');
    }

    public function testItGeneratesNestedFactoryInCorrectPath(): void
    {
        $this->artisan('osdd:factory', ['name' => 'Admin/UserFactory', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/database/factories/Admin/UserFactory.php');
    }

    public function testItGeneratesNestedFactoryWithCorrectNamespace(): void
    {
        $this->artisan('osdd:factory', ['name' => 'Admin/UserFactory', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Database\Factories\Admin;',
            'class UserFactory extends Factory',
        ], 'functional/test-layer/database/factories/Admin/UserFactory.php');
    }
}
