<?php

namespace Xefi\LaravelOSDD\Tests\Console\Commands\Make;

use Orchestra\Testbench\Concerns\InteractsWithPublishedFiles;
use Xefi\LaravelOSDD\Tests\TestCase;

class ObserverMakeCommandTest extends TestCase
{
    use InteractsWithPublishedFiles;

    protected function tearDown(): void
    {
        $this->app['files']->deleteDirectory($this->app->basePath('functional/test-layer/src/Observers'));

        parent::tearDown();
    }

    public function testItGeneratesObserverFileInCorrectPath(): void
    {
        $this->artisan('osdd:observer', ['name' => 'UserObserver', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Observers/UserObserver.php');
    }

    public function testItGeneratesObserverWithCorrectNamespace(): void
    {
        $this->artisan('osdd:observer', ['name' => 'UserObserver', '--layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFileContains([
            'namespace Functional\TestLayer\Observers;',
            'class UserObserver',
        ], 'functional/test-layer/src/Observers/UserObserver.php');
    }

    public function testItPromptsForLayerWhenNotProvided(): void
    {
        $this->artisan('osdd:observer', ['name' => 'UserObserver'])
            ->expectsSearch('Which layer should this be generated in?', 'functional/test-layer', '', ['functional/test-layer' => 'functional/test-layer'])
            ->assertExitCode(0);

        $this->assertFilenameExists('functional/test-layer/src/Observers/UserObserver.php');
    }
}
