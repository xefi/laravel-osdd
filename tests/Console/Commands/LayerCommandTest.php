<?php

namespace Xefi\LaravelOSDD\Tests\Console\Commands;

use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;
use Xefi\LaravelOSDD\Tests\TestCase;

class LayerCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    private string $composerPath;

    private ?string $composerOriginal = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->composerPath = $this->app->basePath('composer.json');

        if ($this->app['files']->exists($this->composerPath)) {
            $this->composerOriginal = $this->app['files']->get($this->composerPath);
        } else {
            $this->app['files']->put($this->composerPath, json_encode(['require' => new \stdClass()], JSON_PRETTY_PRINT) . PHP_EOL);
        }
    }

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/my-layer'));
        $this->app['files']->deleteDirectory($this->app->basePath('functional/my-auth-layer'));

        if ($this->composerOriginal !== null) {
            $this->app['files']->put($this->composerPath, $this->composerOriginal);
        } else {
            $this->app['files']->delete($this->composerPath);
        }

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

    public function testItGeneratesNamedSeederForLayer(): void
    {
        $this->artisan('osdd:layer')
            ->expectsQuestion('Layer name (vendor/package)', 'acme/my-layer')
            ->expectsChoice('Which components should be scaffolded?', $this->allComponents(), $this->allComponents())
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/my-layer/database/seeders/MyLayerSeeder.php');

        $this->assertFileContains([
            'namespace Acme\MyLayer\Database\Seeders;',
            'class MyLayerSeeder extends Seeder',
        ], 'functional/my-layer/database/seeders/MyLayerSeeder.php');
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
            'class MyLayerServiceProvider extends LayerServiceProvider',
            "loadMigrationsFrom(__DIR__ . '/../../database/migrations')",
            'loadSeeders([MyLayerSeeder::class])',
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
            'class MyAuthLayerServiceProvider extends LayerServiceProvider',
        ], 'functional/my-auth-layer/src/Providers/MyAuthLayerServiceProvider.php');
    }

    public function testItRegistersLayerAsPathRepositoryInComposerJson(): void
    {
        $this->artisan('osdd:layer')
            ->expectsQuestion('Layer name (vendor/package)', 'acme/my-layer')
            ->expectsChoice('Which components should be scaffolded?', $this->allComponents(), $this->allComponents())
            ->assertExitCode(0);

        $composer = json_decode($this->app['files']->get($this->composerPath), true);

        $urls = array_column($composer['repositories'] ?? [], 'url');
        $this->assertContains('./functional/my-layer', $urls);

        $types = array_column($composer['repositories'], 'type');
        $this->assertContains('path', $types);
    }

    public function testItAddsLayerToRequireInComposerJson(): void
    {
        $this->artisan('osdd:layer')
            ->expectsQuestion('Layer name (vendor/package)', 'acme/my-layer')
            ->expectsChoice('Which components should be scaffolded?', $this->allComponents(), $this->allComponents())
            ->assertExitCode(0);

        $composer = json_decode($this->app['files']->get($this->composerPath), true);

        $this->assertArrayHasKey('acme/my-layer', $composer['require']);
        $this->assertSame('*', $composer['require']['acme/my-layer']);
    }

    public function testItDoesNotDuplicateRepositoryOnRepeatRuns(): void
    {
        $run = fn() => $this->artisan('osdd:layer')
            ->expectsQuestion('Layer name (vendor/package)', 'acme/my-layer')
            ->expectsChoice('Which components should be scaffolded?', $this->allComponents(), $this->allComponents())
            ->assertExitCode(0);

        $run();
        $run();

        $composer = json_decode($this->app['files']->get($this->composerPath), true);

        $matching = array_filter($composer['repositories'] ?? [], fn($r) => ($r['url'] ?? '') === './functional/my-layer');
        $this->assertCount(1, $matching);
    }

    public function testItSkipsComposerRegistrationWhenNoComposerJsonExists(): void
    {
        $this->app['files']->delete($this->composerPath);

        $this->artisan('osdd:layer')
            ->expectsQuestion('Layer name (vendor/package)', 'acme/my-layer')
            ->expectsChoice('Which components should be scaffolded?', $this->allComponents(), $this->allComponents())
            ->assertExitCode(0);

        // Layer files are still created — registration is just skipped silently
        $this->assertFilenameExists('functional/my-layer/composer.json');
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
