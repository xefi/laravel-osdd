<?php

namespace Xefi\LaravelOSDD\Tests\Console\Commands;

use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;
use Xefi\LaravelOSDD\Tests\TestCase;

class LayerCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/my-layer'));
        $this->app['files']->deleteDirectory($this->app->basePath('functional/my-auth-layer'));

        parent::tearDown();
    }

    public function testItCreatesLayerWithAllComponents(): void
    {
        $this->artisan('osdd:layer')
            ->expectsQuestion('Layer name (vendor/package)', 'acme/my-layer')
            ->expectsChoice('Which components should be scaffolded?', $this->allComponents(), $this->allComponents())
            ->assertExitCode(0);

        foreach ($this->allComponents() as $component) {
            $this->assertFilenameExists("functional/my-layer/{$component}");
        }
    }

    public function testItCreatesCorrectComposerJson(): void
    {
        $this->artisan('osdd:layer')
            ->expectsQuestion('Layer name (vendor/package)', 'acme/my-layer')
            ->expectsChoice('Which components should be scaffolded?', $this->allComponents(), $this->allComponents())
            ->assertExitCode(0);

        $this->assertFileContains([
            '"name": "acme/my-layer"',
            '"type": "layer"',
            '"Acme\\\\MyLayer\\\\": "src/"',
            '"xefi/laravel-osdd": "*"',
        ], 'functional/my-layer/composer.json');
    }

    public function testItCreatesServiceProviderWithCorrectContent(): void
    {
        $this->artisan('osdd:layer')
            ->expectsQuestion('Layer name (vendor/package)', 'acme/my-layer')
            ->expectsChoice('Which components should be scaffolded?', $this->allComponents(), $this->allComponents())
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Acme\MyLayer\Providers;',
            'class MyLayerServiceProvider extends ServiceProvider',
            "loadMigrationsFrom(__DIR__ . '/../../database/migrations')",
        ], 'functional/my-layer/src/Providers/MyLayerServiceProvider.php');
    }

    public function testItCreatesLayerWithSelectedComponents(): void
    {
        $selected = ['database/migrations', 'src/Models'];

        $this->artisan('osdd:layer')
            ->expectsQuestion('Layer name (vendor/package)', 'acme/my-layer')
            ->expectsChoice('Which components should be scaffolded?', $selected, $this->allComponents())
            ->assertExitCode(0);

        foreach ($selected as $component) {
            $this->assertFilenameExists("functional/my-layer/{$component}");
        }

        foreach (array_diff($this->allComponents(), $selected) as $component) {
            $this->assertFilenameNotExists("functional/my-layer/{$component}");
        }
    }

    public function testItSkipsPathPromptWhenOnlyOnePathIsConfigured(): void
    {
        // With a single path configured, select() is skipped — only two prompts fire.
        $this->artisan('osdd:layer')
            ->expectsQuestion('Layer name (vendor/package)', 'acme/my-layer')
            ->expectsChoice('Which components should be scaffolded?', $this->allComponents(), $this->allComponents())
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/my-layer/composer.json');
    }

    public function testItAsksForPathWhenMultiplePathsAreConfigured(): void
    {
        $this->app['config']->set('osdd.layers.paths.technical', $this->app->basePath('technical'));

        $this->artisan('osdd:layer')
            ->expectsQuestion('Layer name (vendor/package)', 'acme/my-layer')
            ->expectsQuestion('Where should the layer be created?', 'functional')
            ->expectsChoice('Which components should be scaffolded?', $this->allComponents(), $this->allComponents())
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/my-layer/composer.json');
        $this->assertFilenameNotExists('technical/my-layer/composer.json');
    }

    public function testItValidatesLayerName(): void
    {
        $this->artisan('osdd:layer')
            ->expectsQuestion('Layer name (vendor/package)', 'InvalidName')
            ->assertFailed();
    }

    public function testNamespaceIsCorrectlyDerivedFromHyphenatedNames(): void
    {
        $this->artisan('osdd:layer')
            ->expectsQuestion('Layer name (vendor/package)', 'acme/my-auth-layer')
            ->expectsChoice('Which components should be scaffolded?', $this->allComponents(), $this->allComponents())
            ->assertExitCode(0);

        $this->assertFileContains([
            '"Acme\\\\MyAuthLayer\\\\": "src/"',
        ], 'functional/my-auth-layer/composer.json');

        $this->assertFileContains([
            'namespace Acme\MyAuthLayer\Providers;',
            'class MyAuthLayerServiceProvider extends ServiceProvider',
        ], 'functional/my-auth-layer/src/Providers/MyAuthLayerServiceProvider.php');
    }

    private function allComponents(): array
    {
        return [
            'database/migrations',
            'database/factories',
            'database/seeders',
            'src/Models',
            'src/Factories',
            'src/Policies',
            'src/Providers',
        ];
    }
}
