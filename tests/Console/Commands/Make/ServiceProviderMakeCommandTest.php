<?php

namespace Xefi\LaravelOSDD\Tests\Console\Commands\Make;

use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;
use Xefi\LaravelOSDD\Tests\TestCase;

class ServiceProviderMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    private string $originalComposerJson;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalComposerJson = $this->app['files']->get(
            $this->app->basePath('functional/test-layer/composer.json')
        );
    }

    protected function tearDown(): void
    {
        $this->app['files']->put(
            $this->app->basePath('functional/test-layer/composer.json'),
            $this->originalComposerJson
        );

        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Providers'));

        parent::tearDown();
    }

    public function testItGeneratesProviderFileInCorrectPath(): void
    {
        $this->artisan('osdd:provider', ['name' => 'TestLayerServiceProvider', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Providers/TestLayerServiceProvider.php');
    }

    public function testItGeneratesProviderWithCorrectNamespace(): void
    {
        $this->artisan('osdd:provider', ['name' => 'TestLayerServiceProvider', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Providers;',
            'class TestLayerServiceProvider extends ServiceProvider',
        ], 'functional/test-layer/src/Providers/TestLayerServiceProvider.php');
    }

    public function testItPromptsForLayerWhenNotProvided(): void
    {
        $this->artisan('osdd:provider', ['name' => 'TestLayerServiceProvider'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Providers/TestLayerServiceProvider.php');
    }

    public function testItRegistersProviderInComposerJson(): void
    {
        $this->artisan('osdd:provider', ['name' => 'TestLayerServiceProvider', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $composer = json_decode(
            $this->app['files']->get($this->app->basePath('functional/test-layer/composer.json')),
            true
        );

        $this->assertContains(
            'Functional\\TestLayer\\Providers\\TestLayerServiceProvider',
            $composer['extra']['laravel']['providers'] ?? []
        );
    }

    public function testItDoesNotDuplicateProviderInComposerJson(): void
    {
        $this->artisan('osdd:provider', ['name' => 'TestLayerServiceProvider', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->artisan('osdd:provider', ['name' => 'TestLayerServiceProvider', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $composer = json_decode(
            $this->app['files']->get($this->app->basePath('functional/test-layer/composer.json')),
            true
        );

        $providers = $composer['extra']['laravel']['providers'] ?? [];
        $this->assertCount(1, array_filter($providers, fn($p) => $p === 'Functional\\TestLayer\\Providers\\TestLayerServiceProvider'));
    }

    public function testItGeneratesNestedProviderInCorrectPath(): void
    {
        $this->artisan('osdd:provider', ['name' => 'Admin/AdminServiceProvider', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Providers/Admin/AdminServiceProvider.php');
    }

    public function testItGeneratesNestedProviderWithCorrectNamespace(): void
    {
        $this->artisan('osdd:provider', ['name' => 'Admin/AdminServiceProvider', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Providers\Admin;',
            'class AdminServiceProvider extends ServiceProvider',
        ], 'functional/test-layer/src/Providers/Admin/AdminServiceProvider.php');
    }
}
