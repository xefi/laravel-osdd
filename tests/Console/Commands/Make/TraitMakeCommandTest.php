<?php

namespace Xefi\LaravelOSDD\Tests\Console\Commands\Make;

use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;
use Xefi\LaravelOSDD\Tests\TestCase;

class TraitMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Concerns'));

        parent::tearDown();
    }

    public function testItGeneratesTraitFileInCorrectPath(): void
    {
        $this->artisan('osdd:trait', ['name' => 'HasTimestamps', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Concerns/HasTimestamps.php');
    }

    public function testItGeneratesTraitWithCorrectNamespace(): void
    {
        $this->artisan('osdd:trait', ['name' => 'HasTimestamps', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Concerns;',
            'trait HasTimestamps',
        ], 'functional/test-layer/src/Concerns/HasTimestamps.php');
    }

    public function testItPromptsForLayerWhenNotProvided(): void
    {
        $this->artisan('osdd:trait', ['name' => 'HasTimestamps'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Concerns/HasTimestamps.php');
    }
}
