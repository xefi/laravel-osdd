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
        $this->app['files']->deleteDirectory($this->app->basePath('technical/my-layer'));

        if ($this->composerOriginal !== null) {
            $this->app['files']->put($this->composerPath, $this->composerOriginal);
        } else {
            $this->app['files']->delete($this->composerPath);
        }

        parent::tearDown();
    }

    public function testItCreatesLayerWithAllGenerators(): void
    {
        $this->artisan('osdd:layer')
            ->expectsQuestion('Layer name', 'my-layer')
            ->expectsChoice('Which generators should be run?', $this->allGenerators(), $this->allGenerators())
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/my-layer/database/migrations');
        $this->assertFilenameExists('functional/my-layer/src/Models');
        $this->assertFilenameExists('functional/my-layer/database/factories');
        $this->assertFilenameExists('functional/my-layer/database/seeders');
        $this->assertFilenameExists('functional/my-layer/src/Providers');
        $this->assertFilenameExists('functional/my-layer/tests/Feature');
        $this->assertFilenameExists('functional/my-layer/src/Http/Controllers');
        $this->assertFilenameExists('functional/my-layer/src/Policies');
    }

    public function testItGeneratesNamedSeederForLayer(): void
    {
        $this->artisan('osdd:layer')
            ->expectsQuestion('Layer name', 'my-layer')
            ->expectsChoice('Which generators should be run?', $this->allGenerators(), $this->allGenerators())
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/my-layer/database/seeders/MyLayerSeeder.php');

        $this->assertFileContains([
            'namespace Functional\MyLayer\Database\Seeders;',
            'class MyLayerSeeder extends Seeder',
        ], 'functional/my-layer/database/seeders/MyLayerSeeder.php');
    }

    public function testItCreatesCorrectComposerJson(): void
    {
        $this->artisan('osdd:layer')
            ->expectsQuestion('Layer name', 'my-layer')
            ->expectsChoice('Which generators should be run?', $this->allGenerators(), $this->allGenerators())
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $this->assertFileContains([
            '"name": "functional/my-layer"',
            '"type": "layer"',
            '"version": "1.0.0"',
            '"Functional\\\\MyLayer\\\\": "src/"',
            '"Functional\\\\MyLayer\\\\Database\\\\Seeders\\\\": "database/seeders/"',
            '"Functional\\\\MyLayer\\\\Database\\\\Factories\\\\": "database/factories/"',
            '"xefi/laravel-osdd": "*"',
            'Functional\\\\MyLayer\\\\Providers\\\\MyLayerServiceProvider',
        ], 'functional/my-layer/composer.json');
    }

    public function testItCreatesServiceProviderWithCorrectContent(): void
    {
        $this->artisan('osdd:layer')
            ->expectsQuestion('Layer name', 'my-layer')
            ->expectsChoice('Which generators should be run?', $this->allGenerators(), $this->allGenerators())
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\MyLayer\Providers;',
            'class MyLayerServiceProvider extends LayerServiceProvider',
            "loadMigrationsFrom(__DIR__ . '/../../database/migrations')",
            'loadSeeders([MyLayerSeeder::class])',
        ], 'functional/my-layer/src/Providers/MyLayerServiceProvider.php');
    }

    public function testItCreatesLayerWithSelectedGenerators(): void
    {
        $selected = ['migration', 'model'];

        $this->artisan('osdd:layer')
            ->expectsQuestion('Layer name', 'my-layer')
            ->expectsChoice('Which generators should be run?', $selected, $this->allGenerators())
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/my-layer/database/migrations');
        $this->assertFilenameExists('functional/my-layer/src/Models');

        $this->assertFilenameNotExists('functional/my-layer/database/factories');
        $this->assertFilenameNotExists('functional/my-layer/database/seeders');
        $this->assertFilenameNotExists('functional/my-layer/src/Providers');
        $this->assertFilenameNotExists('functional/my-layer/tests/Feature');
        $this->assertFilenameNotExists('functional/my-layer/src/Http/Controllers');
        $this->assertFilenameNotExists('functional/my-layer/src/Policies');
    }

    public function testItSkipsPathPromptWhenOnlyOnePathIsConfigured(): void
    {
        $this->artisan('osdd:layer')
            ->expectsQuestion('Layer name', 'my-layer')
            ->expectsChoice('Which generators should be run?', $this->defaultGenerators(), $this->allGenerators())
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/my-layer/composer.json');
    }

    public function testItAsksForPathWhenMultiplePathsAreConfigured(): void
    {
        $this->app['config']->set('osdd.layers.paths.technical', $this->app->basePath('technical'));

        $this->artisan('osdd:layer')
            ->expectsQuestion('Where should the layer be created?', 'functional')
            ->expectsQuestion('Layer name', 'my-layer')
            ->expectsChoice('Which generators should be run?', $this->defaultGenerators(), $this->allGenerators())
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/my-layer/composer.json');
        $this->assertFilenameNotExists('technical/my-layer/composer.json');
    }

    public function testItValidatesLayerName(): void
    {
        $this->artisan('osdd:layer')
            ->expectsQuestion('Layer name', 'InvalidName')
            ->assertFailed();
    }

    public function testNamespaceIsCorrectlyDerivedFromHyphenatedNames(): void
    {
        $this->artisan('osdd:layer')
            ->expectsQuestion('Layer name', 'my-auth-layer')
            ->expectsChoice('Which generators should be run?', $this->allGenerators(), $this->allGenerators())
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $this->assertFileContains([
            '"Functional\\\\MyAuthLayer\\\\": "src/"',
            '"Functional\\\\MyAuthLayer\\\\Database\\\\Seeders\\\\": "database/seeders/"',
        ], 'functional/my-auth-layer/composer.json');

        $this->assertFileContains([
            'namespace Functional\MyAuthLayer\Providers;',
            'class MyAuthLayerServiceProvider extends LayerServiceProvider',
        ], 'functional/my-auth-layer/src/Providers/MyAuthLayerServiceProvider.php');
    }

    public function testItRegistersLayerAsPathRepositoryInComposerJson(): void
    {
        $this->artisan('osdd:layer')
            ->expectsQuestion('Layer name', 'my-layer')
            ->expectsChoice('Which generators should be run?', $this->defaultGenerators(), $this->allGenerators())
            ->expectsConfirmation('Run composer update now?', 'no')
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
            ->expectsQuestion('Layer name', 'my-layer')
            ->expectsChoice('Which generators should be run?', $this->defaultGenerators(), $this->allGenerators())
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $composer = json_decode($this->app['files']->get($this->composerPath), true);

        $this->assertArrayHasKey('functional/my-layer', $composer['require']);
        $this->assertSame('*', $composer['require']['functional/my-layer']);
    }


    public function testItDoesNotDuplicateRepositoryOnRepeatRuns(): void
    {
        $run = fn() => $this->artisan('osdd:layer')
            ->expectsQuestion('Layer name', 'my-layer')
            ->expectsChoice('Which generators should be run?', $this->defaultGenerators(), $this->allGenerators())
            ->expectsConfirmation('Run composer update now?', 'no')
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
            ->expectsQuestion('Layer name', 'my-layer')
            ->expectsChoice('Which generators should be run?', $this->defaultGenerators(), $this->allGenerators())
            ->expectsConfirmation('Run composer update now?', 'no')
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/my-layer/composer.json');
    }

    private function allGenerators(): array
    {
        return [
            'migration',
            'model',
            'factory',
            'seeder',
            'service-provider',
            'test',
            'controller',
            'policy',
        ];
    }

    private function defaultGenerators(): array
    {
        return ['migration', 'model', 'factory', 'service-provider', 'test'];
    }
}
